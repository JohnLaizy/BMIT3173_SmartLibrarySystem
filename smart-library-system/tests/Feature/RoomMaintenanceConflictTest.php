<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomMaintenanceConflictTest extends TestCase
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

    public function test_confirmed_reservation_blocks_maintenance(): void
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

        /*
         * 已有预约：10:00 AM 至 12:00 PM。
         */
        RoomReservation::factory()
            ->for($room)
            ->for($student, 'user')
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
                'status' => RoomReservation::STATUS_CONFIRMED,
            ]);

        /*
         * 尝试建立维修：11:00 AM 至 1:00 PM。
         * 因为和预约重叠，所以必须被拒绝。
         */
        $response = $this
            ->actingAs($librarian)
            ->from(route('maintenances.create'))
            ->post(
                route('maintenances.store'),
                [
                    'room_id' => $room->id,
                    'title' => 'Projector repair',
                    'description' => 'Replace the projector cable.',

                    'starts_at' => '2026-08-11 11:00:00',

                    'ends_at' => '2026-08-11 13:00:00',

                    'status' => RoomMaintenance::STATUS_SCHEDULED,
                ]
            );

        $response
            ->assertRedirect(
                route('maintenances.create')
            )
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount(
            'room_maintenances',
            0
        );
    }

    public function test_cancelled_reservation_does_not_block_maintenance(): void
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

        /*
         * 预约已经被取消，不再占用 Room。
         */
        RoomReservation::factory()
            ->for($room)
            ->for($student, 'user')
            ->cancelled()
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
            ]);

        $response = $this
            ->actingAs($librarian)
            ->post(
                route('maintenances.store'),
                [
                    'room_id' => $room->id,
                    'title' => 'Air conditioner service',
                    'description' => 'Scheduled air conditioner inspection.',

                    'starts_at' => '2026-08-11 11:00:00',

                    'ends_at' => '2026-08-11 13:00:00',

                    'status' => RoomMaintenance::STATUS_SCHEDULED,
                ]
            );

        $response
            ->assertRedirect(
                route('maintenances.index')
            )
            ->assertSessionHas(
                'success',
                'Maintenance scheduled successfully.'
            );

        $this->assertDatabaseHas(
            'room_maintenances',
            [
                'room_id' => $room->id,
                'title' => 'Air conditioner service',

                'status' => RoomMaintenance::STATUS_SCHEDULED,

                'created_by' => $librarian->id,
            ]
        );
    }

    public function test_maintenance_cannot_overlap_another_maintenance(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $room = Room::factory()->create([
            'status' => 'available',
        ]);

        /*
         * 原有维修：10:00 AM 至 12:00 PM。
         */
        RoomMaintenance::factory()
            ->for($room)
            ->create([
                'starts_at' => '2026-08-11 10:00:00',
                'ends_at' => '2026-08-11 12:00:00',
                'status' => RoomMaintenance::STATUS_SCHEDULED,
            ]);

        /*
         * 新维修：11:30 AM 至 1:00 PM。
         */
        $response = $this
            ->actingAs($librarian)
            ->from(route('maintenances.create'))
            ->post(
                route('maintenances.store'),
                [
                    'room_id' => $room->id,
                    'title' => 'Electrical inspection',
                    'description' => 'Inspect the room power outlets.',

                    'starts_at' => '2026-08-11 11:30:00',

                    'ends_at' => '2026-08-11 13:00:00',

                    'status' => RoomMaintenance::STATUS_SCHEDULED,
                ]
            );

        $response
            ->assertRedirect(
                route('maintenances.create')
            )
            ->assertSessionHasErrors('starts_at');

        /*
         * 数据库只能保留原本的一笔 Maintenance。
         */
        $this->assertDatabaseCount(
            'room_maintenances',
            1
        );
    }
}
