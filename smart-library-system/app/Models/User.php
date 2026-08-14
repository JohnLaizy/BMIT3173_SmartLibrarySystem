<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
// For Borrowing and Returning
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $role
 */
#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_STUDENT = 'student';

    public const ROLE_LIBRARIAN = 'librarian';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    // Helpers
    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isLibrarian(): bool
    {
        return $this->role === self::ROLE_LIBRARIAN;
    }

    /**
     * 用户的借书记录。
     *
     * @return HasMany<Borrowing, $this>
     */
    public function borrowings(): HasMany
    {
        return $this->hasMany(
            Borrowing::class
        );
    }

    /**
     * Student 建立的 Room Reservations。
     *
     * @return HasMany<RoomReservation, $this>
     */
    public function roomReservations(): HasMany
    {
        return $this->hasMany(
            RoomReservation::class,
            'user_id'
        );
    }

    /**
     * Librarian 建立的 Room Maintenance 记录。
     *
     * @return HasMany<RoomMaintenance, $this>
     */
    public function createdRoomMaintenances(): HasMany
    {
        return $this->hasMany(
            RoomMaintenance::class,
            'created_by'
        );
    }
}
