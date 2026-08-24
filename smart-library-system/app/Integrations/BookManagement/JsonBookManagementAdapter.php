<?php

namespace App\Integrations\BookManagement;

use App\Contracts\BookManagementPort;

class JsonBookManagementAdapter implements BookManagementPort
{
    //testing purposes
    private string $filePath;

    public function __construct()
    {
        $this->filePath = storage_path(
            'integration/book-management/books.json'
        );
    }

    // Retrieve book information from JSON file
    public function getBook(string $bookId): ?array
    {
        $books = $this->readBooks();

        if ($books === null) {
            return null;
        }

        foreach ($books as $book) {
            if ((string) $book['book_id'] === $bookId) {
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

        foreach ($books as &$book) {
            if ((string) $book['book_id'] === $bookId) {

                if ($book['status'] !== 'AVAILABLE') {
                    return false;
                }

                if (!$book['borrowable']) {
                    return false;
                }

                $book['status'] = 'BORROWED';
                $book['borrowing_id'] = $borrowingId;
                $book['borrowed_by'] = $userId;

                return $this->writeBooks($books);
            }
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

        foreach ($books as &$book) {
            if ((string) $book['book_id'] === $bookId) {

                if ($book['status'] !== 'BORROWED') {
                    return false;
                }

                if (
                    (string) $book['borrowing_id']
                    !== $borrowingId
                ) {
                    return false;
                }

                $book['status'] = 'AVAILABLE';
                $book['borrowing_id'] = null;
                $book['borrowed_by'] = null;

                return $this->writeBooks($books);
            }
        }

        return false;
    }

    //Helper methods to read and write books from/to the JSON file
    private function readBooks(): ?array
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $json = file_get_contents($this->filePath);

        if ($json === false) {
            return null;
        }

        $books = json_decode($json, true);

        if (!is_array($books)) {
            return null;
        }

        return $books;
    }

    private function writeBooks(array $books): bool
    {
        $json = json_encode(
            $books,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return false;
        }

        return file_put_contents(
            $this->filePath,
            $json
        ) !== false;
    }
}