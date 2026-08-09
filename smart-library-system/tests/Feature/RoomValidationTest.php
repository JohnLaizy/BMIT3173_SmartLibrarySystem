<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_create_room_with_valid_data(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->post(
                route('rooms.store'),
                $this->validRoomData()
            );

        $response->assertRedirect(
            route('rooms.index')
        );

        $this->assertDatabaseHas('rooms', [
            'room_number' => 'R900',
            'type' => 'study',
            'capacity' => 6,
            'status' => 'available',
        ]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->post(
                route('rooms.store'),
                $this->validRoomData([
                    'status' => 'hacked',
                ])
            );

        $response->assertSessionHasErrors('status');

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_invalid_facility_is_rejected(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->post(
                route('rooms.store'),
                $this->validRoomData([
                    'facilities' => [
                        'whiteboard',
                        'admin_access',
                    ],
                ])
            );

        $response->assertSessionHasErrors(
            'facilities.1'
        );

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_non_integer_capacity_is_rejected(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->post(
                route('rooms.store'),
                $this->validRoomData([
                    'capacity' => 'six',
                ])
            );

        $response->assertSessionHasErrors('capacity');

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_capacity_outside_allowed_range_is_rejected(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->post(
                route('rooms.store'),
                $this->validRoomData([
                    'capacity' => 101,
                ])
            );

        $response->assertSessionHasErrors('capacity');

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_duplicate_room_number_is_rejected(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        Room::factory()->create([
            'room_number' => 'R900',
        ]);

        $response = $this
            ->actingAs($librarian)
            ->post(
                route('rooms.store'),
                $this->validRoomData()
            );

        $response->assertSessionHasErrors(
            'room_number'
        );

        $this->assertDatabaseCount('rooms', 1);
    }

    public function test_malicious_room_number_is_rejected(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->post(
                route('rooms.store'),
                $this->validRoomData([
                    'room_number' => "R101' OR 1=1 --",
                ])
            );

        $response->assertSessionHasErrors(
            'room_number'
        );

        $this->assertDatabaseCount('rooms', 0);
    }

    private function validRoomData(
        array $overrides = []
    ): array {
        return array_merge([
            'room_number' => 'R900',
            'name' => 'Test Study Room',
            'type' => 'study',
            'capacity' => 6,
            'location' => 'First Floor',
            'status' => 'available',
            'description' => 'Room validation test.',
            'facilities' => [
                'whiteboard',
                'power_outlets',
            ],
        ], $overrides);
    }
}
