<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCopyQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_increase_book_quantity(): void
    {
        $librarian = User::factory()->create([
            'role' => User::ROLE_LIBRARIAN,
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = Book::query()->create([
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'total_copies' => 2,
            'available_copies' => 1,
        ]);

        Borrowing::query()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_BORROWED,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $response = $this
            ->actingAs($librarian)
            ->patch(
                route('books.copies.update', $book),
                ['total_copies' => 4]
            );

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $book->refresh();

        $this->assertSame(4, $book->total_copies);
        $this->assertSame(3, $book->available_copies);
    }

    public function test_quantity_cannot_be_lower_than_active_loans(): void
    {
        $librarian = User::factory()->create([
            'role' => User::ROLE_LIBRARIAN,
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = Book::query()->create([
            'title' => 'Refactoring',
            'author' => 'Martin Fowler',
            'isbn' => '9780134757599',
            'total_copies' => 2,
            'available_copies' => 1,
        ]);

        Borrowing::query()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_BORROWED,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $response = $this
            ->actingAs($librarian)
            ->patch(
                route('books.copies.update', $book),
                ['total_copies' => 0]
            );

        $response
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(
            2,
            $book->fresh()->total_copies
        );
    }

    public function test_student_cannot_manage_book_quantity(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = Book::query()->create([
            'title' => 'Domain-Driven Design',
            'author' => 'Eric Evans',
            'isbn' => '9780321125217',
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $this
            ->actingAs($student)
            ->patch(
                route('books.copies.update', $book),
                ['total_copies' => 5]
            )
            ->assertForbidden();

        $this->assertSame(
            2,
            $book->fresh()->total_copies
        );
    }
}