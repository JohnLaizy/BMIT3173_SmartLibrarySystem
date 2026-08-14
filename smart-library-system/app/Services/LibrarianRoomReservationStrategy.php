<?php

namespace App\Services;

use App\Models\RoomReservation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LibrarianRoomReservationStrategy implements RoomReservationStrategy
{
    public function resolveTargetUser(
        User $actor,
        array $data,
        ?RoomReservation $reservation = null
    ): User {
        /*
         * Librarian Strategy:
         * Librarian 可以替 Student 建立或修改预约，
         * 但必须指定 Student。
         */
        if (! $actor->isLibrarian()) {
            throw ValidationException::withMessages([
                'user' => 'Only librarians can use the librarian reservation strategy.',
            ]);
        }

        if (
            ! isset($data['user_id'])
            || $data['user_id'] === null
        ) {
            throw ValidationException::withMessages([
                'user_id' => 'Select a student for this reservation.',
            ]);
        }

        $targetUser = User::query()
            ->whereKey($data['user_id'])
            ->lockForUpdate()
            ->firstOrFail();

        /*
         * Librarian 也不能把 Reservation
         * 分配给另一个 Librarian。
         */
        if (! $targetUser->isStudent()) {
            throw ValidationException::withMessages([
                'user_id' => 'Reservations can only be assigned to students.',
            ]);
        }

        return $targetUser;
    }
}