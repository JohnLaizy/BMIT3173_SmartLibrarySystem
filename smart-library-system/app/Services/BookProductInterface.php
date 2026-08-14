<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Http\UploadedFile;

interface BookProductInterface
{
    public function persist(array $data, ?UploadedFile $coverImage, ?UploadedFile $bookFile): Book;
}