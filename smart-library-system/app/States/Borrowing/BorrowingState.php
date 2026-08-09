<?php

namespace App\States\Borrowing;

use App\Exceptions\BorrowingRuleViolation;
use App\Models\Borrowing;
use App\Models\User;
use Carbon\CarbonInterface;

abstract class BorrowingState
{
    public function __construct(
        protected Borrowing $borrowing
    ) {}

    abstract public function name(): string;

    public function markOverdue(): void
    {
        $this->invalidTransition('mark it overdue');
    }

    public function returnCopy(
        CarbonInterface $returnedAt,
        int $feeCents
    ): void {
        $this->invalidTransition('return this copy');
    }

    public function submitPayment(
        string $paymentReference,
        CarbonInterface $submittedAt
    ): void {
        $this->invalidTransition(
            'submit an overdue payment'
        );
    }

    public function approvePayment(
        User $librarian,
        CarbonInterface $approvedAt
    ): void {
        $this->invalidTransition(
            'approve this overdue payment'
        );
    }

    public function rejectPayment(): void
    {
        $this->invalidTransition(
            'reject this overdue payment'
        );
    }

    protected function transitionTo(string $status): void
    {
        $this->borrowing->status = $status;
    }

    private function invalidTransition(string $action): never
    {
        throw BorrowingRuleViolation::because(
            "You cannot {$action} while the borrowing is {$this->name()}."
        );
    }
}