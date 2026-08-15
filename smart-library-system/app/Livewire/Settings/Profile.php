<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules;

    /**
     * Profile 表单中的用户名字。
     *
     * 对应 Blade：
     * wire:model="profileName"
     */
    public string $profileName = '';

    /**
     * Profile 表单中的用户 Email。
     *
     * 对应 Blade：
     * wire:model="profileEmail"
     */
    public string $profileEmail = '';

    public string $phone = '';

    /**
     * Profile Component 第一次载入时执行。
     *
     * 把当前登录用户的名字和 Email
     * 载入 Livewire 的公开属性。
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone = Auth::user()->phone ?? '';
    }

    /**
     * 更新当前登录用户的 Profile Information。
     */
    public function updateProfileInformation(): void
    {
        $user = $this->authenticatedUser();

        /*
         * 验证 Livewire 表单里的资料。
         *
         * profileEmail 验证时忽略当前用户自己的 ID，
         * 防止系统认为用户原本的 Email 已被其他人使用。
         */
        $validated = $this->validate([
            'profileName' => $this->nameRules(),
            'profileEmail' => $this->emailRules($user->id),
        ]);

        /*
         * Livewire 属性：
         * profileName / profileEmail
         *
         * Database columns：
         * name / email
         */
        $user->fill([
            'name' => $validated['profileName'],
            'email' => $validated['profileEmail'],
        ]);

        /*
         * 如果用户修改了 Email，
         * 清除旧 Email 的验证时间。
         */
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        /*
         * 保存成功后显示 Toast。
         */
        Flux::toast(
            variant: 'success',
            text: __('Profile updated.')
        );
    }

    /**
     * 重新发送 Email Verification Notification。
     */
    public function resendVerificationNotification(): void
    {
        $user = $this->authenticatedUser();

        /*
         * 如果这个 User 没有启用 MustVerifyEmail，
         * 不需要发送 Email Verification。
         */
        if (! $user instanceof MustVerifyEmail) {
            return;
        }

        /*
         * 如果已经完成验证，就返回 Dashboard。
         */
        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(
                default: route(
                    'dashboard',
                    absolute: false
                )
            );

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(
            text: __(
                'A new verification link has been sent to your email address.'
            )
        );
    }

    /**
     * 检查目前用户的 Email 是否还没有完成验证。
     */
    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = $this->authenticatedUser();

        return $user instanceof MustVerifyEmail
            && ! $user->hasVerifiedEmail();
    }

    /**
     * 决定是否显示 Delete Account 功能。
     */
    #[Computed]
    public function showDeleteUser(): bool
    {
        $user = $this->authenticatedUser();

        return ! $user instanceof MustVerifyEmail
            || $user->hasVerifiedEmail();
    }

    /**
     * 返回当前已经登录的 App\Models\User。
     *
     * Auth::user() 的默认返回类型是：
     *
     * Authenticatable|null
     *
     * 但是这个项目实际使用的用户 Model 是：
     *
     * App\Models\User
     *
     * 通过这个方法进行类型检查以后，
     * PHPStan 和 Intelephense 就能正确识别：
     *
     * fill()
     * save()
     * isDirty()
     * getAttribute()
     * hasVerifiedEmail()
     * sendEmailVerificationNotification()
     */
    private function authenticatedUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(
                401,
                'You must be logged in to manage your profile.'
            );
        }

        return $user;
    }
}
