<?php

namespace App\States\Borrowing;

use App\Models\Borrowing;

class CompletedState extends BorrowingState
{
    public function name(): string
    {
        return Borrowing::STATUS_COMPLETED;
    }
}
