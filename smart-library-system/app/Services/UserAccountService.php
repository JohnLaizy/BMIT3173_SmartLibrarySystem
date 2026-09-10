<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\User;

class UserAccountService
{
    public function __construct(
        private PaymentInformationService $paymentInformationService
    ) {
    }

    public function update(
        User $user,
        array $data,
        bool $isOwnAccount = false
    ): void {
        if ($isOwnAccount) {
            $data['role'] = User::ROLE_LIBRARIAN;
            $data['account_status'] = User::STATUS_ACTIVE;
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }

    public function syncAccountStatus(User $user): void
    {
        // Librarian accounts are not controlled
        // by overdue borrowing/payment status.
        if (! $user->isStudent()) {
            return;
        }

        // Student still has an overdue book
        // that has not yet been resolved.
        $hasUnresolvedOverdue = Borrowing::query()
        ->where('user_id', $user->id)
        ->unresolvedOverdue()
        ->exists();

        // Student has an unpaid or pending payment.
        $hasOutstandingPayment =
            $this->paymentInformationService
                ->hasOutstandingPayment($user->id);

        $newStatus =
          $hasUnresolvedOverdue || $hasOutstandingPayment
            ? User::STATUS_INACTIVE
            : User::STATUS_ACTIVE;

        if ($user->account_status !== $newStatus) {
            $user->account_status = $newStatus;
            $user->save();
        }
    }
}