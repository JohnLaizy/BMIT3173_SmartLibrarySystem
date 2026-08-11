<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, RoomMaintenance> $maintenances
 * @property-read Collection<int, RoomReservation> $reservations
 */
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    public const ALLOWED_FACILITIES = [
        'air_conditioning',
        'whiteboard',
        'projector',
        'power_outlets',
        'computer',
    ];

    public const ALLOWED_TYPES = [
        'study',
        'discussion',
        'meeting',
    ];

    public const ALLOWED_STATUSES = [
        'available',
        'unavailable',
        'maintenance',
    ];

    protected $fillable = [
        'room_number',
        'name',
        'type',
        'capacity',
        'location',
        'status',
        'description',
        'facilities',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'facilities' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<RoomMaintenance, $this>
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(
            RoomMaintenance::class
        );
    }

    /**
     * @return HasMany<RoomReservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(
            RoomReservation::class
        );
    }
}
