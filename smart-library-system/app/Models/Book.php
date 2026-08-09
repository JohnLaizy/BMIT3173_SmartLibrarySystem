<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'author',
    'isbn',
    'total_copies',
    'available_copies',
])]
class Book extends Model
{
    //
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
}
