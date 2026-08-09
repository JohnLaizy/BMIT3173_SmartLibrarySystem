<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_open_create_room_page(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->get(route('rooms.create'));

        $response
            ->assertOk()
            ->assertSee('Add Room');
    }

    public function test_student_cannot_open_create_room_page(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $response = $this
            ->actingAs($student)
            ->get(route('rooms.create'));

        $response->assertForbidden();
    }

    public function test_student_can_view_room_list_without_add_button(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $response = $this
            ->actingAs($student)
            ->get(route('rooms.index'));

        $response
            ->assertOk()
            ->assertSee('Room Management')
            ->assertDontSee('Add Room');
    }

    public function test_student_cannot_open_edit_room_page(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create();

        $response = $this
            ->actingAs($student)
            ->get(route('rooms.edit', $room));

        $response->assertForbidden();
    }

    public function test_student_cannot_update_room(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'capacity' => 6,
            'status' => 'available',
        ]);

        $response = $this
            ->actingAs($student)
            ->put(route('rooms.update', $room), [
                'room_number' => $room->room_number,
                'name' => $room->name,
                'type' => $room->type,
                'capacity' => 20,
                'location' => $room->location,
                'status' => 'unavailable',
                'description' => $room->description,
                'facilities' => ['whiteboard'],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'capacity' => 6,
            'status' => 'available',
        ]);
    }

    public function test_student_cannot_delete_room(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create();

        $response = $this
            ->actingAs($student)
            ->delete(route('rooms.destroy', $room));

        $response->assertForbidden();

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
        ]);
    }

    public function test_librarian_can_update_room(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $room = Room::factory()->create([
            'capacity' => 6,
            'status' => 'available',
        ]);

        $response = $this
            ->actingAs($librarian)
            ->put(route('rooms.update', $room), [
                'room_number' => $room->room_number,
                'name' => $room->name,
                'type' => $room->type,
                'capacity' => 12,
                'location' => $room->location,
                'status' => 'unavailable',
                'description' => $room->description,
                'facilities' => [
                    'whiteboard',
                    'projector',
                ],
            ]);

        $response->assertRedirect(
            route('rooms.show', $room)
        );

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'capacity' => 12,
            'status' => 'unavailable',
        ]);
    }

    public function test_librarian_can_delete_room(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $room = Room::factory()->create();

        $response = $this
            ->actingAs($librarian)
            ->delete(route('rooms.destroy', $room));

        $response->assertRedirect(
            route('rooms.index')
        );

        $this->assertDatabaseMissing('rooms', [
            'id' => $room->id,
        ]);
    }
}
