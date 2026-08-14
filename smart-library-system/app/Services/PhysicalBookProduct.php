<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PhysicalBookProduct implements BookProductInterface
{
    public function persist(array $data, ?UploadedFile $coverImage, ?UploadedFile $bookFile): Book
    {
        $coverPath = null;
        if ($coverImage) {
            $fileName = Str::uuid() . '.' . $coverImage->getClientOriginalExtension();
            $coverPath = $coverImage->storeAs('covers', $fileName, 'public');
        }

        return Book::create([
            'isbn' => $data['isbn'],
            'title' => $data['title'],
            'author' => $data['author'],
            'category' => $data['category'],
            'type' => 'physical',
            'total_copies' => $data['total_copies'],
            'available_copies' => $data['total_copies'],
            'cover_image_path' => $coverPath,
            'file_path' => null,
        ]);
    }
}