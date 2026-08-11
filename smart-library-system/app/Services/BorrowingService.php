<?php

namespace App\Services;

use App\Exceptions\BorrowingRuleViolation;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonInterface;
use LogicException;

class BorrowingService
{
    public function borrow(
        User $student,
        Book $book
    ): Borrowing {
        if (! $student->isStudent()) {
            throw BorrowingRuleViolation::because(
                'Only students may borrow books.'
            );
        }

        return DB::transaction(
            function () use ($student, $book): Borrowing {
                $lockedStudent = User::query()
                    ->lockForUpdate()
                    ->findOrFail($student->id);

                $lockedBook = Book::query()
                    ->lockForUpdate()
                    ->findOrFail($book->id);

                $this->markExpiredBorrowings(
                    $lockedStudent->id
                );

                $hasUnresolvedOverdue =
                    Borrowing::query()
                        ->where(
                            'user_id',
                            $lockedStudent->id
                        )
                        ->unresolvedOverdue()
                        ->exists();

                if ($hasUnresolvedOverdue) {
                    throw BorrowingRuleViolation::because(
                        'You must return overdue books and obtain librarian approval for all overdue payments before borrowing another book.'
                    );
                }

                $activeCopies = Borrowing::query()
                    ->where(
                        'user_id',
                        $lockedStudent->id
                    )
                    ->activeCopies()
                    ->count();

                $borrowLimit = max(
                    1,
                    (int) config(
                        'library.borrow_limit',
                        5
                    )
                );

                if ($activeCopies >= $borrowLimit) {
                    throw BorrowingRuleViolation::because(
                        "Students may borrow no more than {$borrowLimit} book copies at a time."
                    );
                }

                if ($lockedBook->available_copies < 1) {
                    throw BorrowingRuleViolation::because(
                        'This book currently has no available copies.'
                    );
                }

                $borrowedAt = now();

                $loanDays = max(
                    1,
                    (int) config(
                        'library.loan_days',
                        7
                    )
                );

                $dueAt = $borrowedAt
                    ->copy()
                    ->addDays($loanDays);

                $borrowing = Borrowing::query()->create([
                    'user_id' => $lockedStudent->id,
                    'book_id' => $lockedBook->id,
                    'status' =>
                        Borrowing::STATUS_BORROWED,
                    'borrowed_at' => $borrowedAt,
                    'due_at' => $dueAt,
                ]);

                $lockedBook->available_copies--;

                $lockedBook->save();

                Log::info('Book copy borrowed.', [
                    'borrowing_id' => $borrowing->id,
                    'book_id' => $lockedBook->id,
                    'user_id' => $lockedStudent->id,
                    'due_at' => $dueAt->toIso8601String(),
                ]);

                return $borrowing;
            },
            attempts: 3
        );
    }

    public function returnCopy(
        User $actor,
        Borrowing $borrowing
    ): Borrowing {
        return DB::transaction(
            function () use ($actor, $borrowing): Borrowing {
                $lockedBorrowing = Borrowing::query()
                    ->lockForUpdate()
                    ->findOrFail($borrowing->id);

                $isOwner =
                    $actor->isStudent()
                    && $lockedBorrowing->user_id === $actor->id;

                if (! $actor->isLibrarian() && ! $isOwner) {
                    throw BorrowingRuleViolation::because(
                        'You may return only your own borrowed books.'
                    );
                }

                if ($lockedBorrowing->returned_at !== null) {
                    throw BorrowingRuleViolation::because(
                        'This book copy has already been returned.'
                    );
                }

                $returnedAt = now();
                $dueAt = $lockedBorrowing->due_at;

                if ($dueAt === null) {
                    throw new LogicException(
                        'Borrowing due date is missing.'
                    );
                }

                if (
                    $lockedBorrowing->status ===
                        Borrowing::STATUS_BORROWED
                    && $dueAt->isBefore($returnedAt)
                ) {
                    $lockedBorrowing
                        ->state()
                        ->markOverdue();
                }

                $feeCents =
                    $lockedBorrowing->status ===
                        Borrowing::STATUS_OVERDUE
                        ? $this->calculateOverdueFee(
                            $lockedBorrowing,
                            $returnedAt
                        )
                        : 0;

                $lockedBorrowing
                    ->state()
                    ->returnCopy($returnedAt, $feeCents);

                $lockedBorrowing->save();

                $lockedBook = Book::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedBorrowing->book_id);

                $newAvailableCopies = min(
                    (int) $lockedBook->total_copies,
                    (int) $lockedBook->available_copies + 1
                );

                $lockedBook->setAttribute(
                    'available_copies',
                    $newAvailableCopies
                );

                $lockedBook->save();

                Log::info('Book copy returned.', [
                    'borrowing_id' => $lockedBorrowing->id,
                    'book_id' => $lockedBook->id,
                    'user_id' => $lockedBorrowing->user_id,
                    'overdue_fee_cents' =>
                        $lockedBorrowing->overdue_fee_cents,
                ]);

                return $lockedBorrowing;
            },
            attempts: 3
        );
    }

    public function approvePayment(
        User $librarian,
        Borrowing $borrowing
    ): Borrowing {
        if (! $librarian->isLibrarian()) {
            throw BorrowingRuleViolation::because(
                'Only librarians may approve overdue payments.'
            );
        }

        return DB::transaction(
            function () use (
                $librarian,
                $borrowing
            ): Borrowing {
                $lockedBorrowing = Borrowing::query()
                    ->lockForUpdate()
                    ->findOrFail($borrowing->id);

                $lockedBorrowing
                    ->state()
                    ->approvePayment(
                        $librarian,
                        now()
                    );

                $lockedBorrowing->save();

                Log::info('Overdue payment approved.', [
                    'borrowing_id' =>
                        $lockedBorrowing->id,
                    'approved_by' => $librarian->id,
                ]);

                return $lockedBorrowing;
            },
            attempts: 3
        );
    }

    public function rejectPayment(
        User $librarian,
        Borrowing $borrowing
    ): Borrowing {
        if (! $librarian->isLibrarian()) {
            throw BorrowingRuleViolation::because(
                'Only librarians may reject overdue payments.'
            );
        }

        return DB::transaction(
            function () use (
                $librarian,
                $borrowing
            ): Borrowing {
                $lockedBorrowing = Borrowing::query()
                    ->lockForUpdate()
                    ->findOrFail($borrowing->id);

                $lockedBorrowing
                    ->state()
                    ->rejectPayment();

                $lockedBorrowing->save();

                Log::warning('Overdue payment rejected.', [
                    'borrowing_id' =>
                        $lockedBorrowing->id,
                    'rejected_by' => $librarian->id,
                ]);

                return $lockedBorrowing;
            },
            attempts: 3
        );
    }

    private function calculateOverdueFee(
        Borrowing $borrowing,
        CarbonInterface $returnedAt
    ): int {
        $dueAt = $borrowing->due_at;

        if ($dueAt === null) {
            throw new LogicException(
                'Borrowing due date is missing.'
            );
        }

        $secondsOverdue = max(
            0,
            $returnedAt->getTimestamp()
                - $dueAt->getTimestamp()
        );

        // Any partial overdue day counts as one day.
        $daysOverdue = max(
            1,
            (int) ceil($secondsOverdue / 86400)
        );

        $dailyFeeCents = max(
            1,
            (int) config(
                'library.overdue_fee_cents_per_day',
                100
            )
        );

        return $daysOverdue * $dailyFeeCents;
    }

    public function submitPayment(
        User $student,
        Borrowing $borrowing,
        string $paymentReference
    ): Borrowing {
        $paymentReference = trim($paymentReference);

        if ($paymentReference === '') {
            throw BorrowingRuleViolation::because(
                'A payment reference is required.'
            );
        }

        return DB::transaction(
            function () use (
                $student,
                $borrowing,
                $paymentReference
            ): Borrowing {
                $lockedBorrowing = Borrowing::query()
                    ->lockForUpdate()
                    ->findOrFail($borrowing->id);

                $isOwner =
                    $student->isStudent()
                    && $lockedBorrowing->user_id ===
                        $student->id;

                if (! $isOwner) {
                    throw BorrowingRuleViolation::because(
                        'You may submit payment only for your own overdue fee.'
                    );
                }

                $lockedBorrowing
                    ->state()
                    ->submitPayment(
                        $paymentReference,
                        now()
                    );

                $lockedBorrowing->save();

                Log::info(
                    'Overdue payment submitted for approval.',
                    [
                        'borrowing_id' =>
                            $lockedBorrowing->id,
                        'user_id' => $student->id,
                    ]
                );

                return $lockedBorrowing;
            },
            attempts: 3
        );
    }

    public function markOverdueBorrowings(
        ?int $studentId = null
    ): int {
        return DB::transaction(
            fn (): int => $this->markExpiredBorrowings(
                $studentId
            ),
            attempts: 3
        );
    }

    private function markExpiredBorrowings(
        ?int $studentId = null
    ): int {
        $now = now();

        $query = Borrowing::query()
            ->where(
                'status',
                Borrowing::STATUS_BORROWED
            )
            ->whereNull('returned_at')
            ->where('due_at', '<', $now);

        if ($studentId !== null) {
            $query->where('user_id', $studentId);
        }

        $expiredBorrowings = $query
            ->lockForUpdate()
            ->get();

        foreach ($expiredBorrowings as $borrowing) {
            $borrowing->state()->markOverdue();
            $borrowing->save();

            Log::notice(
                'Borrowing automatically marked overdue.',
                [
                    'borrowing_id' => $borrowing->id,
                    'user_id' => $borrowing->user_id,
                ]
            );
        }

        return $expiredBorrowings->count();
    }
}