<?php

namespace App\Services;

use App\Models\User;

class UserAccessService
{
    public function isRestricted(
        User $user,
        string $routeName
    ): bool {
        if (
            $user->isActive() ||
            $user->isLibrarian()
        ) {
            return false;
        }

        $restrictedRoutes = [
            'borrowings.store',
            'book-reservations.store',
            'room-reservations.create',
            'room-reservations.store',
        ];

        return in_array(
            $routeName,
            $restrictedRoutes,
            true
        );
    }
}