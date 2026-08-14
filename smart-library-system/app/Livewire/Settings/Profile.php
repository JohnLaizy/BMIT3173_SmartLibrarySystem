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

    public string $profileName = '';

    public string $profileEmail = '';

    public string $phone = '';

    /**
     * Load the current user's profile information.
     */
    public function mount(): void
    {
        $user = $this->authenticatedUser();

        $this->profileName = $user->name;
        $this->profileEmail = $user->email;
        $this->phone = $user->phone ?? '';
    }

    /**
     * Update the profile information for the current user.
     */
    public function updateProfileInformation(): void
    {
        $user = $this->authenticatedUser();

        $validated = $this->validate([
            'profileName' => $this->nameRules(),
            'profileEmail' => $this->emailRules($user->id),
            'phone' => $this->phoneRules(),
        ]);

        $user->fill([
            'name' => $validated['profileName'],
            'email' => $validated['profileEmail'],
            'phone' => $validated['phone'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(
            variant: 'success',
            text: __('Profile updated.')
        );
    }

    /**
     * Send a new email verification notification.
     */
    public function resendVerificationNotification(): void
    {
        $user = $this->authenticatedUser();

        if (! $user instanceof MustVerifyEmail) {
            return;
        }

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

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = $this->authenticatedUser();

        return $user instanceof MustVerifyEmail
            && ! $user->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        $user = $this->authenticatedUser();

        return ! $user instanceof MustVerifyEmail
            || $user->hasVerifiedEmail();
    }

    /**
     * Return the currently authenticated User model.
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