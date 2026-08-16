<?php

namespace App\Services;

use App\Models\RoomReservation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StudentRoomReservationTargetResolver
    implements RoomReservationTargetResolver
{
    public function resolveTargetUser(
        User $actor,
        array $data,
        ?RoomReservation $reservation = null
    ): User {
        if (! $actor->isStudent()) {
            throw ValidationException::withMessages([
                'user' =>
                    'Only students can make reservations for themselves.',
            ]);
        }

        if (
            $reservation !== null
            && $reservation->user_id !== $actor->id
        ) {
            throw ValidationException::withMessages([
                'reservation' =>
                    'Students can only update their own reservations.',
            ]);
        }

        return User::query()
            ->whereKey($actor->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}