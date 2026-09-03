<?php

namespace App\Integrations\BookManagement;

use App\Contracts\BookManagementPort;

class JsonBookManagementAdapter implements BookManagementPort
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? storage_path(
            'integration/book-management/books.json'
        );
    }

    public function getBook(string $bookId): ?array
    {
        $books = $this->readBooks();

        if ($books === null) {
            return null;
        }

        foreach ($books as $book) {
            if (
                is_array($book)
                && (string) ($book['book_id'] ?? '') === $bookId
            ) {
                return $book;
            }
        }

        return null;
    }

    public function markBorrowed(
        string $bookId,
        string $borrowingId,
        string $userId
    ): bool {
        $books = $this->readBooks();

        if ($books === null) {
            return false;
        }

        foreach ($books as $index => $book) {
            if (
                ! is_array($book)
                || (string) ($book['book_id'] ?? '') !== $bookId
            ) {
                continue;
            }

            if (($book['borrowable'] ?? false) !== true) {
                return false;
            }

            $availableCopies = (int) (
                $book['available_copies'] ?? 0
            );

            if ($availableCopies <= 0) {
                return false;
            }

            $books[$index]['available_copies'] =
                $availableCopies - 1;

            return $this->writeBooks($books);
        }

        return false;
    }

    public function markReturned(
        string $bookId,
        string $borrowingId
    ): bool {
        $books = $this->readBooks();

        if ($books === null) {
            return false;
        }

        foreach ($books as $index => $book) {
            if (
                ! is_array($book)
                || (string) ($book['book_id'] ?? '') !== $bookId
            ) {
                continue;
            }

            $totalCopies = (int) (
                $book['total_copies'] ?? 0
            );

            $availableCopies = (int) (
                $book['available_copies'] ?? 0
            );

            if (
                $totalCopies <= 0
                || $availableCopies >= $totalCopies
            ) {
                return false;
            }

            $books[$index]['available_copies'] =
                $availableCopies + 1;

            return $this->writeBooks($books);
        }

        return false;
    }

    private function readBooks(): ?array
    {
        if (! file_exists($this->filePath)) {
            return null;
        }

        $json = file_get_contents($this->filePath);

        if ($json === false) {
            return null;
        }

        $books = json_decode($json, true);

        return is_array($books) ? $books : null;
    }

    private function writeBooks(array $books): bool
    {
        $json = json_encode(
            $books,
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return false;
        }

        return file_put_contents(
            $this->filePath,
            $json.PHP_EOL,
            LOCK_EX
        ) !== false;
    }
}