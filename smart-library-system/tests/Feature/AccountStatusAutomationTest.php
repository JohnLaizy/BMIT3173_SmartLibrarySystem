<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStatusAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_becomes_inactive_for_unresolved_overdue_and_active_after_resolution(): void
    {
        $student = User::factory()->student()->create([
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $book = $this->createBook();

        $borrowing = Borrowing::query()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_OVERDUE,
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);

        $student->refresh();

        $this->assertSame(
            User::STATUS_INACTIVE,
            $student->account_status
        );

        $borrowing->status = Borrowing::STATUS_FEE_UNPAID;
        $borrowing->save();

        $student->refresh();

        $this->assertSame(
            User::STATUS_INACTIVE,
            $student->account_status
        );

        $borrowing->status = Borrowing::STATUS_PAYMENT_PENDING;
        $borrowing->save();

        $student->refresh();

        $this->assertSame(
            User::STATUS_INACTIVE,
            $student->account_status
        );

        $borrowing->status = Borrowing::STATUS_COMPLETED;
        $borrowing->save();

        $student->refresh();

        $this->assertSame(
            User::STATUS_ACTIVE,
            $student->account_status
        );
    }

    public function test_student_remains_inactive_if_another_unresolved_overdue_exists(): void
    {
        $student = User::factory()->student()->create([
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $firstBook = $this->createBook();
        $secondBook = $this->createBook();

        $firstBorrowing = Borrowing::query()->create([
            'user_id' => $student->id,
            'book_id' => $firstBook->id,
            'status' => Borrowing::STATUS_PAYMENT_PENDING,
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);

        Borrowing::query()->create([
            'user_id' => $student->id,
            'book_id' => $secondBook->id,
            'status' => Borrowing::STATUS_FEE_UNPAID,
            'borrowed_at' => now()->subDays(12),
            'due_at' => now()->subDays(5),
        ]);

        $student->refresh();

        $this->assertSame(
            User::STATUS_INACTIVE,
            $student->account_status
        );

        // First payment is resolved.
        $firstBorrowing->status = Borrowing::STATUS_COMPLETED;
        $firstBorrowing->save();

        $student->refresh();

        // Second unresolved overdue still exists,
        // therefore the account must remain inactive.
        $this->assertSame(
            User::STATUS_INACTIVE,
            $student->account_status
        );
    }

    private function createBook(): Book
    {
        return Book::query()->create([
            'isbn' => '978'.fake()->unique()->numerify('##########'),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'category' => 'Testing',
            'type' => 'physical',
            'total_copies' => 1,
            'available_copies' => 1,
        ]);
    }
}