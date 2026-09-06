<?php

namespace App\Services;

use App\Exceptions\BorrowReturnApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BorrowReturnApiService
{
    /** @param array<int, int> $bookIds
     * @return array<int, int>
     */
    public function activeBorrowingCounts(array $bookIds): array
    {
        $bookIds = array_values(array_unique(array_map('intval', $bookIds)));

        if ($bookIds === []) {
            return [];
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.borrow_return.timeout', 3))
                ->retry(1, 100, throw: false)
                ->get(
                    rtrim((string) config('services.borrow_return.url'), '/').'/borrowings/active-counts',
                    ['book_ids' => implode(',', $bookIds)]
                );
        } catch (ConnectionException $exception) {
            Log::warning('Borrow & Return API connection failed.', ['exception' => $exception->getMessage()]);
            throw new BorrowReturnApiException('Borrowing availability is temporarily unavailable.', 503);
        }

        if (! $response->successful()) {
            $status = in_array($response->status(), [408, 504], true) ? 504 : 502;
            Log::warning('Borrow & Return API returned an error.', ['status' => $response->status()]);
            throw new BorrowReturnApiException('Borrowing availability is temporarily unavailable.', $status);
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            Log::warning('Borrow & Return API returned malformed JSON.');
            throw new BorrowReturnApiException('Borrowing availability returned an invalid response.');
        }

        $counts = [];
        foreach ($bookIds as $bookId) {
            $value = $data[(string) $bookId] ?? $data[$bookId] ?? 0;

            if (! is_numeric($value) || (int) $value < 0) {
                Log::warning('Borrow & Return API returned an invalid count.', ['book_id' => $bookId]);
                throw new BorrowReturnApiException('Borrowing availability returned an invalid response.');
            }

            $counts[$bookId] = (int) $value;
        }

        return $counts;
    }
}
