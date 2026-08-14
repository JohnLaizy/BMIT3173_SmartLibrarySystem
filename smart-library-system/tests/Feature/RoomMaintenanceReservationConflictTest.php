<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomMaintenanceReservationConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_scheduled_maintenance_blocks_reservation(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        /*
         * 维修时间：10:00 AM 至 12:00 PM。
         */
        RoomMaintenance::factory()
            ->for($room)
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
                'status' => RoomMaintenance::STATUS_SCHEDULED,
            ]);

        /*
         * 预约时间：11:00 AM 至 12:00 PM。
         * 与维修发生重叠，所以必须被拒绝。
         */
        $response = $this
            ->actingAs($student)
            ->from(route('room-reservations.create'))
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $room->id,
                    'purpose' => 'Group revision',
                    'starts_at' => '2026-08-11 11:00:00',
                    'ends_at' => '2026-08-11 12:00:00',
                ]
            );

        $response
            ->assertRedirect(
                route('room-reservations.create')
            )
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseCount(
            'room_reservations',
            0
        );
    }

    public function test_in_progress_maintenance_blocks_reservation(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        RoomMaintenance::factory()
            ->for($room)
            ->inProgress()
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
            ]);

        $response = $this
            ->actingAs($student)
            ->from(route('room-reservations.create'))
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $room->id,
                    'purpose' => 'Study session',
                    'starts_at' => '2026-08-11 11:00:00',
                    'ends_at' => '2026-08-11 12:00:00',
                ]
            );

        $response
            ->assertRedirect(
                route('room-reservations.create')
            )
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseCount(
            'room_reservations',
            0
        );
    }

    public function test_completed_maintenance_does_not_block_reservation(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        /*
         * 时间虽然重叠，但是维修已经完成，
         * 所以不应该继续阻挡预约。
         */
        RoomMaintenance::factory()
            ->for($room)
            ->completed()
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
            ]);

        $response = $this
            ->actingAs($student)
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $room->id,
                    'purpose' => 'Completed maintenance test',
                    'starts_at' => '2026-08-11 11:00:00',
                    'ends_at' => '2026-08-11 12:00:00',
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
                'room_id' => $room->id,
                'user_id' => $student->id,
                'status' => RoomReservation::STATUS_CONFIRMED,
            ]
        );
    }

    public function test_cancelled_maintenance_does_not_block_reservation(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        RoomMaintenance::factory()
            ->for($room)
            ->cancelled()
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
            ]);

        $response = $this
            ->actingAs($student)
            ->post(
                route('room-reservations.store'),
                [
                    'room_id' => $room->id,
                    'purpose' => 'Cancelled maintenance test',
                    'starts_at' => '2026-08-11 11:00:00',
                    'ends_at' => '2026-08-11 12:00:00',
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
                'status' => RoomReservation::STATUS_CONFIRMED,
            ]
        );
    }
}
