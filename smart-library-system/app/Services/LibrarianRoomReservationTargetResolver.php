<?php

namespace App\Services;

use App\Models\RoomReservation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LibrarianRoomReservationTargetResolver
    implements RoomReservationTargetResolver
{
    public function resolveTargetUser(
        User $actor,
        array $data,
        ?RoomReservation $reservation = null
    ): User {
        if (! $actor->isLibrarian()) {
            throw ValidationException::withMessages([
                'user' =>
                    'Only librarians can manage reservations for students.',
            ]);
        }

        if (
            ! isset($data['user_id'])
            || $data['user_id'] === null
        ) {
            throw ValidationException::withMessages([
                'user_id' =>
                    'Select a student for this reservation.',
            ]);
        }

        $targetUser = User::query()
            ->whereKey($data['user_id'])
            ->lockForUpdate()
            ->firstOrFail();

        if (! $targetUser->isStudent()) {
            throw ValidationException::withMessages([
                'user_id' =>
                    'Reservations can only be assigned to students.',
            ]);
        }

        return $targetUser;
    }
}