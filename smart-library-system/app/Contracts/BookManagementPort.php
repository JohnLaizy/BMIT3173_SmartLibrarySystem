<?php

namespace App\Contracts;

interface BookManagementPort
{
    public function getBook(string $bookId): ?array;

    public function markBorrowed(
        string $bookId,
        string $borrowingId,
        string $userId
    ): bool;

    public function markReturned(
        string $bookId,
        string $borrowingId
    ): bool;
}