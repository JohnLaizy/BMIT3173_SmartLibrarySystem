<?php

namespace App\Services;

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
}