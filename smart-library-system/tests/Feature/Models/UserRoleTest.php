<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */

    public function test_a_normal_user_is_a_student(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isLibrarian());
    }

    public function test_a_librarian_can_be_identified(): void
    {
        $librarian = User::factory()->librarian()->create();

        $this->assertTrue($librarian->isLibrarian());
        $this->assertFalse($librarian->isStudent());
    }
}
