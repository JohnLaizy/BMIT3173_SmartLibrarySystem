<?php

namespace App\Integrations\UserManagement;

use App\Contracts\UserManagementPort;

class JsonUserManagementAdapter implements UserManagementPort
{
    //testing purposes
    private string $filePath;

    public function __construct()
    {
        $this->filePath = storage_path(
            'integration/user-management/users.json'
        );
    }

    // Retrieve user information from JSON file
    public function getUser(string $userId): ?array
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $json = file_get_contents($this->filePath);

        if ($json === false) {
            return null;
        }

        $users = json_decode($json, true);

        if (!is_array($users)) {
            return null;
        }

        foreach ($users as $user) {
            if ((string) $user['user_id'] === $userId) {
                return $user;
            }
        }

        return null;
    }
}