<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_LIBRARIAN,
            User::ROLE_STUDENT,
        ], true);
    }

    public function view(User $user, Room $room): bool
    {
        return in_array($user->role, [
            User::ROLE_LIBRARIAN,
            User::ROLE_STUDENT,
        ], true);
    }

    public function create(User $user): bool
    {
        return $user->isLibrarian();
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isLibrarian();
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->isLibrarian();
    }

    public function restore(User $user, Room $room): bool
    {
        return $user->isLibrarian();
    }

    public function forceDelete(User $user, Room $room): bool
    {
        return $user->isLibrarian();
    }
}
