<?php

namespace App\States\Borrowing;

use App\Models\Borrowing;
use Carbon\CarbonInterface;

class BorrowedState extends BorrowingState
{
    public function name(): string
    {
        return Borrowing::STATUS_BORROWED;
    }

    public function markOverdue(): void
    {
        $this->transitionTo(
            Borrowing::STATUS_OVERDUE
        );
    }

    public function returnCopy(
        CarbonInterface $returnedAt,
        int $feeCents
    ): void {
        $this->borrowing->setAttribute(
            'returned_at',
            $returnedAt
        );
        $this->borrowing->overdue_fee_cents = 0;

        $this->transitionTo(
            Borrowing::STATUS_COMPLETED
        );
    }
}