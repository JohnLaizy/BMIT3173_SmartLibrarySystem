<?php

namespace App\Observers;

use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoomObserver
{
    public function created(Room $room): void
    {
        Log::info('Room created.', [
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'status' => $room->status,
            'performed_by' => Auth::id(),
        ]);
    }

    public function updated(Room $room): void
    {
        $changes = $room->getChanges();

        unset($changes['updated_at']);

        $originalValues = [];

        foreach (array_keys($changes) as $field) {
            $originalValues[$field] = $room->getRawOriginal($field);
        }

        Log::info('Room updated.', [
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'changed_fields' => array_keys($changes),
            'original_values' => $originalValues,
            'new_values' => $changes,
            'performed_by' => Auth::id(),
        ]);
    }

    public function deleted(Room $room): void
    {
        Log::warning('Room deleted.', [
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'performed_by' => Auth::id(),
        ]);
    }
}
