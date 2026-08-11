<?php

namespace App\States\Borrowing;

use App\Models\Borrowing;
use Carbon\CarbonInterface;

class FeeUnpaidState extends BorrowingState
{
    public function name(): string
    {
        return Borrowing::STATUS_FEE_UNPAID;
    }

    public function submitPayment(
        string $paymentReference,
        CarbonInterface $submittedAt
    ): void {
        $this->borrowing->payment_reference =
            $paymentReference;

        $this->borrowing->setAttribute(
            'payment_submitted_at',
            $submittedAt
        );

        $this->transitionTo(
            Borrowing::STATUS_PAYMENT_PENDING
        );
    }
}