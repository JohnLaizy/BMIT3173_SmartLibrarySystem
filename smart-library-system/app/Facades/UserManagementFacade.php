<?php

namespace App\Facades;

use App\Models\User;
use App\Services\UserAccessService;
use App\Services\UserAccountService;
use App\Services\UserSearchService;

class UserManagementFacade
{
    public function __construct(
        private UserSearchService $userSearchService,
        private UserAccountService $userAccountService,
        private UserAccessService $userAccessService,
    ) {
    }

    public function searchUsers(string $search = '')
    {
        return $this->userSearchService
            ->search($search);
    }

    public function updateUser(
        User $user,
        array $data,
        bool $isOwnAccount = false
    ): void {
        $this->userAccountService->update(
            $user,
            $data,
            $isOwnAccount
        );
    }

    public function isUserActionRestricted(
        User $user,
        string $routeName
    ): bool {
        return $this->userAccessService
            ->isRestricted(
                $user,
                $routeName
            );
    }
}