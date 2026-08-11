<?php

namespace App\Policies;

use App\Models\RoomMaintenance;
use App\Models\User;

class RoomMaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLibrarian();
    }

    public function view(
        User $user,
        RoomMaintenance $maintenance
    ): bool {
        return $user->isLibrarian();
    }

    public function create(User $user): bool
    {
        return $user->isLibrarian();
    }

    public function update(
        User $user,
        RoomMaintenance $maintenance
    ): bool {
        return $user->isLibrarian();
    }

    public function delete(
        User $user,
        RoomMaintenance $maintenance
    ): bool {
        return $user->isLibrarian();
    }
}
