<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. 呼叫你刚刚新建的 LibrarianSeeder
        $this->call([
            LibrarianSeeder::class,
        ]);

        // 2. 保留这个默认的 Test User，顺便把它设定为普通学生 (Student)，用来测试权限拦截
        User::factory()->create([
            'name' => 'Student User',
            'email' => 'test@example.com',
            'role' => 'student', 
        ]);
    }
}