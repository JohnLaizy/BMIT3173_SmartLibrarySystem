<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $casts = [
        'total_copies' => 'integer',
        'available_copies' => 'integer',
    ];
}