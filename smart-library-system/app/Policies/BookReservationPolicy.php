<?php

namespace App\Policies;

use App\Models\BookReservation;
use App\Models\User;

class BookReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStudent()
            || $user->isLibrarian();
    }

    public function view(
        User $user,
        BookReservation $reservation
    ): bool {
        return $user->isLibrarian()
            || $reservation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function approve(
        User $user,
        BookReservation $reservation
    ): bool {
        return $user->isLibrarian()
            && $reservation->isPending();
    }

    public function reject(
        User $user,
        BookReservation $reservation
    ): bool {
        return $user->isLibrarian()
            && $reservation->isPending();
    }

    public function cancel(
        User $user,
        BookReservation $reservation
    ): bool {
        $canBeCancelled = in_array(
            $reservation->status,
            BookReservation::ACTIVE_STATUSES,
            true
        );

        return $canBeCancelled
            && (
                $user->isLibrarian()
                || $reservation->user_id === $user->id
            );
    }

    public function collect(
        User $user,
        BookReservation $reservation
    ): bool {
        return $user->isLibrarian()
            && $reservation->isApproved();
    }
}