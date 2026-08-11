<?php

namespace App\States\Borrowing;

use App\Models\Borrowing;
use Carbon\CarbonInterface;

class OverdueState extends BorrowingState
{
    public function name(): string
    {
        return Borrowing::STATUS_OVERDUE;
    }

    public function returnCopy(
        CarbonInterface $returnedAt,
        int $feeCents
    ): void {
        $this->borrowing->setAttribute(
            'returned_at',
            $returnedAt
        );
        $this->borrowing->overdue_fee_cents = $feeCents;

        $this->transitionTo(
            Borrowing::STATUS_FEE_UNPAID
        );
    }
}
