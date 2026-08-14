<?php

namespace App\Services;

use App\Exceptions\BorrowingRuleViolation;
use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookReservationService
{
    public function request(
        User $student,
        Book $book
    ): BookReservation {
        if (! $student->isStudent()) {
            throw BorrowingRuleViolation::because(
                'Only students may reserve books.'
            );
        }

        return DB::transaction(
            function () use (
                $student,
                $book
            ): BookReservation {
                $lockedStudent = User::query()
                    ->lockForUpdate()
                    ->findOrFail($student->id);

                $lockedBook = Book::query()
                    ->lockForUpdate()
                    ->findOrFail($book->id);

                $this->expireApprovedReservations(
                    $lockedBook->id
                );

                $hasActiveReservation =
                    BookReservation::query()
                        ->where(
                            'user_id',
                            $lockedStudent->id
                        )
                        ->where(
                            'book_id',
                            $lockedBook->id
                        )
                        ->active()
                        ->exists();

                if ($hasActiveReservation) {
                    throw BorrowingRuleViolation::because(
                        'You already have an active reservation for this book.'
                    );
                }

                $alreadyBorrowing = Borrowing::query()
                    ->where(
                        'user_id',
                        $lockedStudent->id
                    )
                    ->where(
                        'book_id',
                        $lockedBook->id
                    )
                    ->whereNull('returned_at')
                    ->exists();

                if ($alreadyBorrowing) {
                    throw BorrowingRuleViolation::because(
                        'You cannot reserve a book you currently have on loan.'
                    );
                }

                $reservation =
                    BookReservation::query()->create([
                        'user_id' => $lockedStudent->id,
                        'book_id' => $lockedBook->id,
                        'status' =>
                            BookReservation::STATUS_PENDING,
                        'requested_at' => now(),
                    ]);

                Log::info('Book reservation requested.', [
                    'reservation_id' => $reservation->id,
                    'book_id' => $lockedBook->id,
                    'user_id' => $lockedStudent->id,
                ]);

                return $reservation;
            },
            attempts: 3
        );
    }

    public function approve(
        User $librarian,
        BookReservation $reservation
    ): BookReservation {
        if (! $librarian->isLibrarian()) {
            throw BorrowingRuleViolation::because(
                'Only librarians may approve reservations.'
            );
        }

        return DB::transaction(
            function () use (
                $librarian,
                $reservation
            ): BookReservation {
                $lockedReservation =
                    BookReservation::query()
                        ->lockForUpdate()
                        ->findOrFail($reservation->id);

                if (! $lockedReservation->isPending()) {
                    throw BorrowingRuleViolation::because(
                        'Only pending reservations may be approved.'
                    );
                }

                $lockedBook = Book::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $lockedReservation->book_id
                    );

                $this->expireApprovedReservations(
                    $lockedBook->id
                );

                $approvedReservations =
                    BookReservation::query()
                        ->where(
                            'book_id',
                            $lockedBook->id
                        )
                        ->approved()
                        ->lockForUpdate()
                        ->count();

                if (
                    $lockedBook->available_copies
                    <= $approvedReservations
                ) {
                    throw BorrowingRuleViolation::because(
                        'No unreserved copy is currently available.'
                    );
                }

                $holdDays = max(
                    1,
                    (int) config(
                        'library.reservation_hold_days',
                        2
                    )
                );

                $lockedReservation->update([
                    'status' =>
                        BookReservation::STATUS_APPROVED,
                    'reviewed_by' => $librarian->id,
                    'reviewed_at' => now(),
                    'expires_at' =>
                        now()->addDays($holdDays),
                    'rejection_reason' => null,
                ]);

                Log::info('Book reservation approved.', [
                    'reservation_id' =>
                        $lockedReservation->id,
                    'reviewed_by' => $librarian->id,
                ]);

                return $lockedReservation;
            },
            attempts: 3
        );
    }

    public function reject(
        User $librarian,
        BookReservation $reservation,
        ?string $reason = null
    ): BookReservation {
        if (! $librarian->isLibrarian()) {
            throw BorrowingRuleViolation::because(
                'Only librarians may reject reservations.'
            );
        }

        return DB::transaction(
            function () use (
                $librarian,
                $reservation,
                $reason
            ): BookReservation {
                $lockedReservation =
                    BookReservation::query()
                        ->lockForUpdate()
                        ->findOrFail($reservation->id);

                if (! $lockedReservation->isPending()) {
                    throw BorrowingRuleViolation::because(
                        'Only pending reservations may be rejected.'
                    );
                }

                $lockedReservation->update([
                    'status' =>
                        BookReservation::STATUS_REJECTED,
                    'reviewed_by' => $librarian->id,
                    'reviewed_at' => now(),
                    'expires_at' => null,
                    'rejection_reason' =>
                        filled($reason) ? trim($reason) : null,
                ]);

                return $lockedReservation;
            },
            attempts: 3
        );
    }

    public function cancel(
        User $actor,
        BookReservation $reservation
    ): BookReservation {
        return DB::transaction(
            function () use (
                $actor,
                $reservation
            ): BookReservation {
                $lockedReservation =
                    BookReservation::query()
                        ->lockForUpdate()
                        ->findOrFail($reservation->id);

                $isOwner =
                    $actor->isStudent()
                    && $lockedReservation->user_id
                        === $actor->id;

                if (! $actor->isLibrarian() && ! $isOwner) {
                    throw BorrowingRuleViolation::because(
                        'You cannot cancel this reservation.'
                    );
                }

                if (
                    ! in_array(
                        $lockedReservation->status,
                        BookReservation::ACTIVE_STATUSES,
                        true
                    )
                ) {
                    throw BorrowingRuleViolation::because(
                        'This reservation can no longer be cancelled.'
                    );
                }

                $lockedReservation->update([
                    'status' =>
                        BookReservation::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'expires_at' => null,
                ]);

                return $lockedReservation;
            },
            attempts: 3
        );
    }

    public function expireApprovedReservations(
        ?int $bookId = null
    ): int {
        $query = BookReservation::query()
            ->where(
                'status',
                BookReservation::STATUS_APPROVED
            )
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        if ($bookId !== null) {
            $query->where('book_id', $bookId);
        }

        return $query->update([
            'status' =>
                BookReservation::STATUS_EXPIRED,
            'updated_at' => now(),
        ]);
    }
}