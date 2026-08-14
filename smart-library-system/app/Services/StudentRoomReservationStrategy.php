<?php

namespace App\Services;

use App\Models\RoomReservation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StudentRoomReservationStrategy implements RoomReservationStrategy
{
    public function resolveTargetUser(
        User $actor,
        array $data,
        ?RoomReservation $reservation = null
    ): User {
        /*
         * Student Strategy:
         * Student 只能替自己进行预约。
         */
        if (! $actor->isStudent()) {
            throw ValidationException::withMessages([
                'user' => 'Only students can use the student reservation strategy.',
            ]);
        }

        /*
         * 如果是 Update，
         * 再确认预约确实属于当前 Student。
         */
        if (
            $reservation !== null
            && $reservation->user_id !== $actor->id
        ) {
            throw ValidationException::withMessages([
                'reservation' => 'Students can only update their own reservations.',
            ]);
        }

        return User::query()
            ->whereKey($actor->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}