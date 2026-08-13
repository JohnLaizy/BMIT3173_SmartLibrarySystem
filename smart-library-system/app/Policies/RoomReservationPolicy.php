<?php

namespace App\Policies;

use App\Models\RoomReservation;
use App\Models\User;

class RoomReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStudent()
            || $user->isLibrarian();
    }

    public function view(
        User $user,
        RoomReservation $reservation
    ): bool {
        return $user->isLibrarian()
            || $reservation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStudent()
            || $user->isLibrarian();
    }

    public function update(
        User $user,
        RoomReservation $reservation
    ): bool {
        if (
            $reservation->status !== RoomReservation::STATUS_CONFIRMED
            || $reservation->ends_at->isPast()
        ) {
            return false;
        }

        return $user->isLibrarian()
            || $reservation->user_id === $user->id;
    }

    public function cancel(
        User $user,
        RoomReservation $reservation
    ): bool {
        if (! $reservation->canBeCancelled()) {
            return false;
        }

        return $user->isLibrarian()
            || $reservation->user_id === $user->id;
    }
}