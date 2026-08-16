<?php

namespace App\Observers;

use App\Models\Borrowing;
use App\Services\UserAccountService;

class BorrowingObserver
{
    public function __construct(
        private UserAccountService $userAccountService
    ) {
    }

    public function saved(Borrowing $borrowing): void
    {
        $student = $borrowing->student()->first();

        if (! $student) {
            return;
        }

        $this->userAccountService
            ->syncAccountStatus($student);
    }
}