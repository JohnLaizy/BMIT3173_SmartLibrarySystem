<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Http\UploadedFile;

abstract class BookFactory
{
    abstract public function createBookProduct(): BookProductInterface;

    public function registerBook(array $data, ?UploadedFile $coverImage, ?UploadedFile $bookFile): Book
    {
        $product = $this->createBookProduct();
        return $product->persist($data, $coverImage, $bookFile);
    }
}