<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\User;

class UserAccountService
{
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
        // Librarian accounts are not controlled by overdue borrowing status.
        if (! $user->isStudent()) {
            return;
        }

        $hasUnresolvedOverdue = Borrowing::query()
            ->where('user_id', $user->id)
            ->unresolvedOverdue()
            ->exists();

        $newStatus = $hasUnresolvedOverdue
            ? User::STATUS_INACTIVE
            : User::STATUS_ACTIVE;

        if ($user->account_status !== $newStatus) {
            $user->account_status = $newStatus;
            $user->save();
        }
    }

}