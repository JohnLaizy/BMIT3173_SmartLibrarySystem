<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'isbn',
        'title',
        'author',
        'category',
        'type',
        'total_copies',
        'available_copies',
        'cover_image_path',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'total_copies' => 'integer',
            'available_copies' => 'integer',
        ];
    }

    public function hasAvailableCopies(): bool
    {
        return $this->available_copies > 0;
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(
            BookReservation::class
        );
    }
}