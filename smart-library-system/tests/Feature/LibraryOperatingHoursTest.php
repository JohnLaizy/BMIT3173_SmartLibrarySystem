<?php

namespace Tests\Feature;

use App\Models\LibrarySetting;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryOperatingHoursTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 普通模式应该产生十二个一小时时段。
     */
    public function test_regular_hours_generate_twelve_time_slots(): void
    {
        $date = CarbonImmutable::tomorrow()
            ->startOfDay();

        $availability =
            app(RoomAvailabilityService::class)
                ->forDate($date);

        $this->assertFalse(
            $availability['librarySetting']
                ->exam_period_enabled
        );

        $this->assertCount(
            12,
            $availability['slots']
        );

        $this->assertSame(
            '08:00',
            $availability['slots']
                ->first()
                ->format('H:i')
        );

        /*
         * 最后一个 Slot 是 19:00–20:00，
         * 所以 Slot 开始时间是 19:00。
         */
        $this->assertSame(
            '19:00',
            $availability['slots']
                ->last()
                ->format('H:i')
        );

        $this->assertSame(
            '20:00',
            $availability['closingAt']
                ->format('H:i')
        );
    }

    /**
     * Librarian 可以开启 Exam Period。
     */
    public function test_librarian_can_enable_exam_period(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->from(route('room-availability.index'))
            ->patch(
                route(
                    'library-settings.exam-period.update'
                ),
                [
                    'enabled' => true,

                    'exam_period_starts_on' =>
                        CarbonImmutable::tomorrow()
                            ->format('Y-m-d'),

                    'exam_period_ends_on' =>
                        CarbonImmutable::tomorrow()
                            ->addWeeks(2)
                            ->format('Y-m-d'),
                ]
                            );

        $response
            ->assertRedirect(
                route('room-availability.index')
            )
            ->assertSessionHas('success');

        $this->assertDatabaseHas(
            'library_settings',
            [
                'exam_period_enabled' => true,
                'updated_by' => $librarian->id,
            ]
        );
    }

    /**
     * Student 不可以修改 Exam Period。
     */
    public function test_student_cannot_enable_exam_period(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $response = $this
            ->actingAs($student)
            ->patch(
                route(
                    'library-settings.exam-period.update'
                ),
                [
                    'enabled' => true,
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas(
            'library_settings',
            [
                'exam_period_enabled' => false,
                'updated_by' => null,
            ]
        );
    }

    /**
     * Exam Period 应该产生十七个时段，
     * 并在第二天凌晨一点关闭。
     */
    public function test_exam_period_generates_slots_until_next_day_one_am(): void
    {
        LibrarySetting::current()->update([
            'exam_period_enabled' => true,
        ]);

        $date = CarbonImmutable::tomorrow()
            ->startOfDay();

        $availability =
            app(RoomAvailabilityService::class)
                ->forDate($date);

        $this->assertCount(
            17,
            $availability['slots']
        );

        $this->assertSame(
            $date->format('Y-m-d').' 08:00',
            $availability['slots']
                ->first()
                ->format('Y-m-d H:i')
        );

        /*
         * 最后一个 Slot 是第二天 00:00–01:00。
         */
        $this->assertSame(
            $date->addDay()->format('Y-m-d').' 00:00',
            $availability['slots']
                ->last()
                ->format('Y-m-d H:i')
        );

        $this->assertSame(
            $date->addDay()->format('Y-m-d').' 01:00',
            $availability['closingAt']
                ->format('Y-m-d H:i')
        );
    }

    /**
     * 普通模式应该拒绝晚上八点之后的预约。
     */
    public function test_regular_hours_reject_after_hours_reservation(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        $date = CarbonImmutable::tomorrow()
            ->startOfDay();

        $response = $this
            ->actingAs($student)
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $room->id,
                    'purpose' => 'Group discussion',

                    'starts_at' => $date
                        ->setTime(21, 0)
                        ->format('Y-m-d H:i:s'),

                    'ends_at' => $date
                        ->setTime(22, 0)
                        ->format('Y-m-d H:i:s'),
                ]
            );

        $response->assertSessionHasErrors(
            'starts_at'
        );

        $this->assertDatabaseCount(
            'room_reservations',
            0
        );
    }

    /**
     * Exam Period 应该允许晚上八点之后的预约。
     */
    public function test_exam_period_allows_after_hours_reservation(): void
    {
        LibrarySetting::current()->update([
            'exam_period_enabled' => true,
        ]);

        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        $date = CarbonImmutable::tomorrow()
            ->startOfDay();

        $startsAt = $date->setTime(22, 0);
        $endsAt = $date->setTime(23, 0);

        $response = $this
            ->actingAs($student)
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $room->id,
                    'purpose' => 'Exam revision',

                    'starts_at' => $startsAt->format(
                        'Y-m-d H:i:s'
                    ),

                    'ends_at' => $endsAt->format(
                        'Y-m-d H:i:s'
                    ),
                ]
            );

        $response->assertRedirect(
            route('room-reservations.index')
        );

        $this->assertDatabaseHas(
            'room_reservations',
            [
                'room_id' => $room->id,
                'user_id' => $student->id,
                'purpose' => 'Exam revision',
                'status' => 'confirmed',
            ]
        );
    }
    /**
 * Exam Period 延长时间只能在设置的日期范围内生效。
 *
 * 开始前：8:00 AM–8:00 PM
 * 期间：8:00 AM–1:00 AM
 * 结束后：8:00 AM–8:00 PM
 */
public function test_exam_period_hours_only_apply_within_configured_dates(): void
{
    $startsOn = CarbonImmutable::tomorrow()
        ->startOfDay();

    $endsOn = $startsOn->addDays(2);

    LibrarySetting::current()->update([
        'exam_period_enabled' => true,

        'exam_period_starts_on' =>
            $startsOn->format('Y-m-d'),

        'exam_period_ends_on' =>
            $endsOn->format('Y-m-d'),
    ]);

    /*
     * Exam Period 开始前。
     */
    $beforePeriod =
        app(RoomAvailabilityService::class)
            ->forDate(
                $startsOn->subDay()
            );

    $this->assertCount(
        12,
        $beforePeriod['slots']
    );

    $this->assertSame(
        '20:00',
        $beforePeriod['closingAt']
            ->format('H:i')
    );

    /*
     * Exam Period 日期范围内。
     */
    $duringPeriod =
        app(RoomAvailabilityService::class)
            ->forDate(
                $startsOn
            );

    $this->assertCount(
        17,
        $duringPeriod['slots']
    );

    $this->assertSame(
        $startsOn
            ->addDay()
            ->format('Y-m-d').' 01:00',
        $duringPeriod['closingAt']
            ->format('Y-m-d H:i')
    );

    /*
     * Exam Period 结束后的第二天。
     */
    $afterPeriod =
        app(RoomAvailabilityService::class)
            ->forDate(
                $endsOn->addDay()
            );

    $this->assertCount(
        12,
        $afterPeriod['slots']
    );

    $this->assertSame(
        '20:00',
        $afterPeriod['closingAt']
            ->format('H:i')
    );
}
}
