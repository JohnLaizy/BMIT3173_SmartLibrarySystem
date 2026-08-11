<?php

namespace App\Console\Commands;

use App\Services\BorrowingService;
use Illuminate\Console\Command;

class MarkOverdueBorrowings extends Command
{
    protected $signature =
        'borrowings:mark-overdue';

    protected $description =
        'Mark expired borrowed books as overdue';

    public function handle(
        BorrowingService $service
    ): int {
        $markedCount =
            $service->markOverdueBorrowings();

        $this->info(
            "{$markedCount} borrowing record(s) marked overdue."
        );

        return self::SUCCESS;
    }
}
