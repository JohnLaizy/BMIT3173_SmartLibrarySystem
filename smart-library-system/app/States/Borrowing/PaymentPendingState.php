<?php

namespace App\States\Borrowing;

use App\Models\Borrowing;
use App\Models\User;
use Carbon\CarbonInterface;

class PaymentPendingState extends BorrowingState
{
    public function name(): string
    {
        return Borrowing::STATUS_PAYMENT_PENDING;
    }

    public function approvePayment(
        User $librarian,
        CarbonInterface $approvedAt
    ): void {
        $this->borrowing->setAttribute(
            'payment_approved_at',
            $approvedAt
        );

        $this->borrowing->payment_approved_by =
            $librarian->id;

        $this->transitionTo(
            Borrowing::STATUS_COMPLETED
        );
    }

    public function rejectPayment(): void
    {
        $this->borrowing->payment_reference = null;
        $this->borrowing->payment_submitted_at = null;

        $this->transitionTo(
            Borrowing::STATUS_FEE_UNPAID
        );
    }
}
