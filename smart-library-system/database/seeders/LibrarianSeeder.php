<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LibrarianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 使用 factory 创建一个默认的 Librarian 账号
        User::factory()->create([
            'name' => 'Library Admin',
            'email' => 'librarian@example.com',
            'role' => 'librarian', 
            // 密码默认由 factory 决定，通常是 'password'
        ]);
    }
}