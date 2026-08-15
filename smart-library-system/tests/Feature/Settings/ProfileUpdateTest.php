<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 确认已登录用户可以打开 Profile Settings 页面。
     */
    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get('/settings/profile')
            ->assertOk();
    }

    /**
     * 确认用户可以更新自己的 Name 和 Email。
     */
    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test(Profile::class)
            ->set('profileName', 'Test User')
            ->set('profileEmail', 'test@example.com')
            ->set('phone', '012-3456789')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals(
            'Test User',
            $user->name
        );

        $this->assertEquals(
            'test@example.com',
            $user->email
        );

        $this->assertEquals(
            '012-3456789',
            $user->phone
        );

        /*
         * Email 改变后，系统必须移除原本的验证状态，
         * 要求用户重新验证新的 Email。
         */
        $this->assertNull(
            $user->email_verified_at
        );
    }

    /**
     * 确认 Email 没有变化时，
     * 系统不会错误清除 email_verified_at。
     */
    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test(Profile::class)
            ->set('profileName', 'Test User')
            ->set('profileEmail', $user->email)
            ->set('phone', '012-3456789')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull(
            $user->refresh()->email_verified_at
        );
    }

    /**
     * 确认用户可以删除自己的 account。
     */
    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('settings.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull(
            $user->fresh()
        );

        $this->assertFalse(
            Auth::check()
        );
    }

    /**
     * 确认错误 password 不能删除 account。
     */
    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('settings.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors([
            'password',
        ]);

        $this->assertNotNull(
            $user->fresh()
        );
    }

    /**
     * 确认 Profile Livewire component 初次载入时，
     * 会显示目前登录用户的 Name 和 Email。
     */
    public function test_profile_component_loads_the_authenticated_users_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile Test User',
            'email' => 'profile-test@example.com',
            'phone' => '012-3456789',
        ]);

        $this->actingAs($user);

        /*
         * 这条测试验证 Profile.php 的 mount()：
         *
         * Auth user
         *     ↓
         * $profileName / $profileEmail
         *     ↓
         * Blade wire:model
         */
        Livewire::test(Profile::class)
            ->assertSet(
                'profileName',
                'Profile Test User'
            )
            ->assertSet(
                'profileEmail',
                'profile-test@example.com'
            )
            ->assertSet(
                'phone',
                '012-3456789'
            );
    }
}
