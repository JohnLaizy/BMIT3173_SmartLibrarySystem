<?php

namespace App\Contracts;

interface UserManagementPort
{
    public function getUser(string $userId): ?array;
}