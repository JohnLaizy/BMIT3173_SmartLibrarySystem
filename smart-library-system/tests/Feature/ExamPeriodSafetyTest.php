<?php

namespace Tests\Feature;

use App\Models\LibrarySetting;
use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamPeriodSafetyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 如果还有未来晚上 8 点后的预约，
     * 系统不允许关闭 Exam Period。
     */
    public function test_exam_period_cannot_be_disabled_when_after_hours_reservation_exists(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        $examStart = CarbonImmutable::today();

        $examEnd = CarbonImmutable::today()
            ->addWeeks(2);

        $setting = LibrarySetting::current();

        $setting->update([
            'exam_period_enabled' => true,

            'exam_period_starts_on' => $examStart->format('Y-m-d'),

            'exam_period_ends_on' => $examEnd->format('Y-m-d'),
        ]);

        /*
         * 建立明天晚上 9:00–10:00 的预约。
         * 这个预约超过普通模式的晚上 8:00。
         */
        $startsAt = CarbonImmutable::tomorrow()
            ->setTime(21, 0);

        $endsAt = $startsAt->addHour();

        RoomReservation::factory()->create([
            'room_id' => $room->id,
            'user_id' => $student->id,
            'purpose' => 'Exam revision',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => RoomReservation::STATUS_CONFIRMED,
        ]);

        $response = $this
            ->actingAs($librarian)
            ->from(
                route('room-availability.index')
            )
            ->patch(
                route(
                    'library-settings.exam-period.update'
                ),
                [
                    'enabled' => false,
                ]
            );

        /*
         * 系统应该返回原本页面，并显示 enabled 错误。
         */
        $response
            ->assertRedirect(
                route('room-availability.index')
            )
            ->assertSessionHasErrors('enabled');

        /*
         * Exam Period 必须继续保持开启。
         */
        $this->assertTrue(
            (bool) $setting
                ->fresh()
                ?->exam_period_enabled
        );
    }

    /**
     * 已取消的晚间预约不会阻止 Librarian
     * 关闭 Exam Period。
     */
    public function test_cancelled_after_hours_reservation_does_not_block_disabling_exam_period(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        $setting = LibrarySetting::current();

        $setting->update([
            'exam_period_enabled' => true,

            'exam_period_starts_on' => CarbonImmutable::today()
                ->format('Y-m-d'),

            'exam_period_ends_on' => CarbonImmutable::today()
                ->addWeeks(2)
                ->format('Y-m-d'),
        ]);

        $startsAt = CarbonImmutable::tomorrow()
            ->setTime(21, 0);

        /*
         * 这笔预约已经取消，所以不应该阻止关闭。
         */
        RoomReservation::factory()
            ->cancelled()
            ->create([
                'room_id' => $room->id,
                'user_id' => $student->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addHour(),
            ]);

        $response = $this
            ->actingAs($librarian)
            ->from(
                route('room-availability.index')
            )
            ->patch(
                route(
                    'library-settings.exam-period.update'
                ),
                [
                    'enabled' => false,
                ]
            );

        $response
            ->assertRedirect(
                route('room-availability.index')
            )
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors();

        /*
         * 没有有效的晚间预约后，应该成功关闭。
         */
        $this->assertFalse(
            (bool) $setting
                ->fresh()
                ?->exam_period_enabled
        );
    }

    /**
     * 如果修改 Exam Period 的结束日期会影响
     * 已经确认的晚间预约，系统必须拒绝修改。
     */
    public function test_exam_period_end_date_cannot_be_shortened_past_existing_after_hours_reservation(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        $setting = LibrarySetting::current();

        /*
         * 原本 Exam Period 从今天开始，
         * 两个星期后才结束。
         */
        $originalStartsOn =
            CarbonImmutable::today();

        $originalEndsOn =
            CarbonImmutable::today()
                ->addDays(14);

        $setting->update([
            'exam_period_enabled' => true,

            'exam_period_starts_on' => $originalStartsOn
                ->format('Y-m-d'),

            'exam_period_ends_on' => $originalEndsOn
                ->format('Y-m-d'),
        ]);

        /*
         * 建立第 10 天晚上 9:00–10:00 的预约。
         *
         * 这个预约目前处于 Exam Period 范围内，
         * 所以是有效预约。
         */
        $reservationStartsAt =
            CarbonImmutable::today()
                ->addDays(10)
                ->setTime(21, 0);

        RoomReservation::factory()->create([
            'room_id' => $room->id,
            'user_id' => $student->id,
            'purpose' => 'Final examination revision',

            'starts_at' => $reservationStartsAt,

            'ends_at' => $reservationStartsAt
                ->addHour(),

            'status' => RoomReservation::STATUS_CONFIRMED,
        ]);

        /*
         * Librarian 尝试把结束日期提前至第 5 天。
         *
         * 如果允许修改，第 10 天的晚间预约
         * 将会变成普通时间以外的无效预约。
         */
        $proposedEndsOn =
            CarbonImmutable::today()
                ->addDays(5);

        $response = $this
            ->actingAs($librarian)
            ->from(
                route('room-availability.index')
            )
            ->patch(
                route(
                    'library-settings.exam-period.update'
                ),
                [
                    'enabled' => true,

                    'exam_period_starts_on' => $originalStartsOn
                        ->format('Y-m-d'),

                    'exam_period_ends_on' => $proposedEndsOn
                        ->format('Y-m-d'),
                ]
            );

        /*
         * 系统应拒绝新的结束日期。
         */
        $response
            ->assertRedirect(
                route('room-availability.index')
            )
            ->assertSessionHasErrors(
                'exam_period_ends_on'
            );

        $refreshedSetting =
            $setting->fresh();

        /*
         * Exam Period 仍然开启。
         */
        $this->assertTrue(
            (bool) $refreshedSetting
                ->exam_period_enabled
        );

        /*
         * 原本的结束日期不能被改变。
         */
        $this->assertSame(
            $originalEndsOn->format('Y-m-d'),

            $refreshedSetting
                ->exam_period_ends_on
                ?->format('Y-m-d')
        );

        /*
         * 预约也不能被系统删除或修改。
         */
        $this->assertDatabaseHas(
            'room_reservations',
            [
                'room_id' => $room->id,
                'user_id' => $student->id,
                'status' => RoomReservation::STATUS_CONFIRMED,
            ]
        );
    }
}
