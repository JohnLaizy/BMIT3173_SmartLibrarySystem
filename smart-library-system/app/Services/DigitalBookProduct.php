<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DigitalBookProduct implements BookProductInterface
{
    public function persist(array $data, ?UploadedFile $coverImage, ?UploadedFile $bookFile): Book
    {
        $coverPath = null;
        if ($coverImage) {
            $fileName = Str::uuid() . '.' . $coverImage->getClientOriginalExtension();
            $coverPath = $coverImage->storeAs('covers', $fileName, 'public');
        }

        $filePath = null;
        if ($bookFile) {
            $fileName = Str::uuid() . '.' . $bookFile->getClientOriginalExtension();
            $filePath = $bookFile->storeAs('ebooks_secured', $fileName, 'local');
        }

        return Book::create([
            'isbn' => $data['isbn'],
            'title' => $data['title'],
            'author' => $data['author'],
            'category' => $data['category'],
            'type' => 'ebook',
            'total_copies' => 1,
            'available_copies' => 1,
            'cover_image_path' => $coverPath,
            'file_path' => $filePath,
        ]);
    }
}