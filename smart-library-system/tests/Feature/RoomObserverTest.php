<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RoomObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_room_is_logged(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $this->actingAs($librarian);

        Log::spy();

        $room = Room::factory()->create([
            'room_number' => 'R901',
            'status' => 'available',
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(
                function (
                    string $message,
                    array $context
                ) use ($room, $librarian): bool {
                    return $message === 'Room created.'
                        && $context['room_id'] === $room->id
                        && $context['room_number'] === 'R901'
                        && $context['status'] === 'available'
                        && $context['performed_by']
                            === $librarian->id;
                }
            );
    }

    public function test_updated_room_changes_are_logged(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $room = Room::factory()->create([
            'room_number' => 'R902',
            'capacity' => 6,
        ]);

        $this->actingAs($librarian);

        Log::spy();

        $room->update([
            'capacity' => 10,
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(
                function (
                    string $message,
                    array $context
                ) use ($room, $librarian): bool {
                    return $message === 'Room updated.'
                        && $context['room_id'] === $room->id
                        && $context['room_number'] === 'R902'
                        && in_array(
                            'capacity',
                            $context['changed_fields'],
                            true
                        )
                        && (int) $context[
                            'original_values'
                        ]['capacity'] === 6
                        && (int) $context[
                            'new_values'
                        ]['capacity'] === 10
                        && $context['performed_by']
                            === $librarian->id;
                }
            );
    }

    public function test_deleted_room_is_logged(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $room = Room::factory()->create([
            'room_number' => 'R903',
        ]);

        $this->actingAs($librarian);

        Log::spy();

        $room->delete();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(
                function (
                    string $message,
                    array $context
                ) use ($room, $librarian): bool {
                    return $message === 'Room deleted.'
                        && $context['room_id'] === $room->id
                        && $context['room_number'] === 'R903'
                        && $context['performed_by']
                            === $librarian->id;
                }
            );
    }
}
