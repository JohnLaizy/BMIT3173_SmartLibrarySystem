<?php

namespace App\Policies;

use App\Models\LibrarySetting;
use App\Models\User;

class LibrarySettingPolicy
{
    /**
     * Student 和 Librarian 都能够看到
     * 当前图书馆开放时间。
     */
    public function view(
        User $user,
        LibrarySetting $librarySetting
    ): bool {
        return $user->isStudent()
            || $user->isLibrarian();
    }

    /**
     * 只有 Librarian 可以更新图书馆设置，
     * 包括开启或关闭 Exam Period。
     */
    public function update(
        User $user,
        LibrarySetting $librarySetting
    ): bool {
        return $user->isLibrarian();
    }
}
