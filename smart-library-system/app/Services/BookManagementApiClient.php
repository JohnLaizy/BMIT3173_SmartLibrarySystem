<?php

namespace App\Services;

use App\Exceptions\BookManagementApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookManagementApiClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchBooks(string $search = ''): array
    {
        $baseUrl = rtrim(
            (string) config('services.book_management.url'),
            '/'
        );

        if ($baseUrl === '') {
            throw new BookManagementApiException(
                'Book Management API URL is not configured.',
                500
            );
        }

        $client = Http::acceptJson()
            ->timeout(
                (int) config(
                    'services.book_management.timeout',
                    3
                )
            )
            ->retry(1, 100, throw: false);

        $token = (string) config(
            'services.book_management.token',
            ''
        );

        if ($token !== '') {
            $client = $client->withToken($token);
        }

        try {
            $response = $client->get(
                $baseUrl.'/books',
                array_filter([
                    'search' => $search,
                    'per_page' => 50,
                ], fn ($value) => $value !== '')
            );
        } catch (ConnectionException $exception) {
            Log::warning(
                'Book Management API connection failed.',
                ['exception' => $exception->getMessage()]
            );

            throw new BookManagementApiException(
                'Book Management is temporarily unavailable.',
                503
            );
        }

        if (! $response->successful()) {
            Log::warning(
                'Book Management API returned an error.',
                ['status' => $response->status()]
            );

            throw new BookManagementApiException(
                'Book Management returned an invalid response.',
                502
            );
        }

        $apiData = $response->json('data');

        /*
         * Supports both:
         *
         * { "data": [ ... ] }
         *
         * and Laravel paginator output:
         *
         * { "data": { "data": [ ... ], "meta": ... } }
         */
        $books = is_array($apiData)
            ? ($apiData['data'] ?? $apiData)
            : null;

        if (! is_array($books)) {
            throw new BookManagementApiException(
                'Book Management returned malformed JSON.',
                502
            );
        }

        return collect($books)
            ->filter(
                fn ($book) =>
                    is_array($book)
                    && isset($book['id'])
            )
            ->map(
                fn (array $book): array => [
                    'id' => (int) $book['id'],
                    'title' => (string) ($book['title'] ?? ''),
                    'author' => (string) ($book['author'] ?? ''),
                    'isbn' => (string) ($book['isbn'] ?? ''),
                    'category' => (string) ($book['category'] ?? ''),
                    'type' => (string) ($book['type'] ?? ''),
                    'total_copies' => max(
                        0,
                        (int) ($book['total_copies'] ?? 0)
                    ),
                    'available_copies' => max(
                        0,
                        (int) ($book['available_copies'] ?? 0)
                    ),
                    'borrowable' => (bool) (
                        $book['borrowable']
                        ?? (
                            ($book['type'] ?? '') === 'physical'
                            && (int) ($book['available_copies'] ?? 0) > 0
                        )
                    ),
                ]
            )
            ->values()
            ->all();
    }
}