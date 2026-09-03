<?php

namespace App\Policies;

use App\Models\Borrowing;
use App\Models\User;

class BorrowingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStudent()
            || $user->isLibrarian();
    }

    public function view(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->isLibrarian()
            || (
                $user->isStudent()
                && $borrowing->user_id === $user->id
            );
    }

    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function returnCopy(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->isLibrarian()
            || (
                $user->isStudent()
                && $borrowing->user_id === $user->id
            );
    }

    public function submitPayment(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->isStudent()
            && $borrowing->user_id === $user->id;
    }

    public function approvePayment(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->isLibrarian();
    }

    public function rejectPayment(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->isLibrarian();
    }

    public function requestRenewal(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->isStudent()
            && $borrowing->user_id === $user->id;
    }

    public function approveRenewal(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->isLibrarian();
    }

    public function rejectRenewal(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->isLibrarian();
    }
}