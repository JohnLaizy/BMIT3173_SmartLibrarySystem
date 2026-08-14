<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomReservationValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * 固定时间，确保下面所有预约都属于未来。
         */
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-10 10:00:00'
            )
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_same_room_cannot_be_reserved_for_overlapping_time(): void
    {
        $firstStudent = User::factory()
            ->student()
            ->create();

        $secondStudent = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        /*
         * 原有预约：10:00 AM 至 12:00 PM。
         */
        RoomReservation::factory()
            ->for($room)
            ->for($firstStudent, 'user')
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
            ]);

        /*
         * 新预约：11:00 AM 至 12:30 PM。
         * 因为时间发生重叠，所以必须被拒绝。
         */
        $response = $this
            ->actingAs($secondStudent)
            ->from(route('room-reservations.create'))
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $room->id,
                    'purpose' => 'Project discussion',
                    'starts_at' => '2026-08-11 11:00:00',
                    'ends_at' => '2026-08-11 12:30:00',
                ]
            );

        $response
            ->assertRedirect(
                route('room-reservations.create')
            )
            ->assertSessionHasErrors('room_id');

        /*
         * 数据库仍然只能有原本的一笔预约。
         */
        $this->assertDatabaseCount(
            'room_reservations',
            1
        );
    }

    public function test_different_rooms_can_be_reserved_at_same_time(): void
    {
        $firstStudent = User::factory()
            ->student()
            ->create();

        $secondStudent = User::factory()
            ->student()
            ->create();

        $firstRoom = Room::factory()->create([
            'status' => 'available',
        ]);

        $secondRoom = Room::factory()->create([
            'status' => 'available',
        ]);

        RoomReservation::factory()
            ->for($firstRoom)
            ->for($firstStudent, 'user')
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 11:00:00',
            ]);

        /*
         * 不同学生预约不同房间，
         * 即使时间相同也应该成功。
         */
        $response = $this
            ->actingAs($secondStudent)
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $secondRoom->id,
                    'purpose' => 'Revision session',
                    'starts_at' => '2026-08-11 10:00:00',
                    'ends_at' => '2026-08-11 11:00:00',
                ]
            );

        $response
            ->assertRedirect(
                route('room-reservations.index')
            )
            ->assertSessionHas(
                'success',
                'Room reserved successfully.'
            );

        $this->assertDatabaseHas(
            'room_reservations',
            [
                'room_id' => $secondRoom->id,
                'user_id' => $secondStudent->id,
                'purpose' => 'Revision session',
                'status' => RoomReservation::STATUS_CONFIRMED,
            ]
        );

        $this->assertDatabaseCount(
            'room_reservations',
            2
        );
    }

    public function test_student_cannot_reserve_two_rooms_at_same_time(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $firstRoom = Room::factory()->create([
            'status' => 'available',
        ]);

        $secondRoom = Room::factory()->create([
            'status' => 'available',
        ]);

        RoomReservation::factory()
            ->for($firstRoom)
            ->for($student, 'user')
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
            ]);

        /*
         * 房间不同，但是同一个学生的时间发生重叠。
         */
        $response = $this
            ->actingAs($student)
            ->from(route('room-reservations.create'))
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $secondRoom->id,
                    'purpose' => 'Another meeting',
                    'starts_at' => '2026-08-11 11:00:00',
                    'ends_at' => '2026-08-11 12:00:00',
                ]
            );

        $response
            ->assertRedirect(
                route('room-reservations.create')
            )
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount(
            'room_reservations',
            1
        );
    }

    public function test_student_cannot_submit_another_students_user_id(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $otherStudent = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        /*
         * Student 尝试修改 HTML Request，
         * 伪造 otherStudent 的 user_id。
         */
        $response = $this
            ->actingAs($student)
            ->from(route('room-reservations.create'))
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $room->id,

                    'user_id' => $otherStudent->id,

                    'purpose' => 'Forged reservation',

                    'starts_at' => '2026-08-11 10:00:00',

                    'ends_at' => '2026-08-11 11:00:00',
                ]
            );

        $response
            ->assertRedirect(
                route('room-reservations.create')
            )
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount(
            'room_reservations',
            0
        );
    }
}
