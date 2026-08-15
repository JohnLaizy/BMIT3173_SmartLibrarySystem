<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_request_book_reservation(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = $this->createBook();

        $this
            ->actingAs($student)
            ->post(
                route('book-reservations.store'),
                ['book_id' => $book->id]
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas(
            'book_reservations',
            [
                'user_id' => $student->id,
                'book_id' => $book->id,
                'status' =>
                    BookReservation::STATUS_PENDING,
            ]
        );
    }

    public function test_duplicate_active_reservation_is_rejected(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = $this->createBook();

        BookReservation::query()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' =>
                BookReservation::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $this
            ->actingAs($student)
            ->post(
                route('book-reservations.store'),
                ['book_id' => $book->id]
            )
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount(
            'book_reservations',
            1
        );
    }

    public function test_librarian_can_approve_reservation(): void
    {
        $librarian = User::factory()->create([
            'role' => User::ROLE_LIBRARIAN,
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = $this->createBook();

        $reservation =
            BookReservation::query()->create([
                'user_id' => $student->id,
                'book_id' => $book->id,
                'status' =>
                    BookReservation::STATUS_PENDING,
                'requested_at' => now(),
            ]);

        $this
            ->actingAs($librarian)
            ->patch(
                route(
                    'book-reservations.approve',
                    $reservation
                )
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $reservation->refresh();

        $this->assertSame(
            BookReservation::STATUS_APPROVED,
            $reservation->status
        );

        $this->assertSame(
            $librarian->id,
            $reservation->reviewed_by
        );

        $this->assertNotNull(
            $reservation->expires_at
        );
    }

    public function test_student_cannot_approve_reservation(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = $this->createBook();

        $reservation =
            BookReservation::query()->create([
                'user_id' => $student->id,
                'book_id' => $book->id,
                'status' =>
                    BookReservation::STATUS_PENDING,
                'requested_at' => now(),
            ]);

        $this
            ->actingAs($student)
            ->patch(
                route(
                    'book-reservations.approve',
                    $reservation
                )
            )
            ->assertForbidden();
    }

    private function createBook(): Book
    {
        return Book::query()->create([
            'title' => 'Design Patterns',
            'author' => 'Erich Gamma',
            'isbn' => fake()->unique()->isbn13(),
            'category' => 'Software Engineering',
            'type' => Book::TYPE_PHYSICAL,
            'total_copies' => 2,
            'available_copies' => 2,
        ]);
    }

    public function test_librarian_can_collect_approved_reservation(): void
    {
        $librarian = User::factory()->create([
            'role' => User::ROLE_LIBRARIAN,
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = $this->createBook();

        $reservation =
            BookReservation::query()->create([
                'user_id' => $student->id,
                'book_id' => $book->id,
                'status' =>
                    BookReservation::STATUS_APPROVED,
                'requested_at' => now()->subDay(),
                'reviewed_at' => now(),
                'reviewed_by' => $librarian->id,
                'expires_at' => now()->addDays(2),
            ]);

        $this
            ->actingAs($librarian)
            ->patch(
                route(
                    'book-reservations.collect',
                    $reservation
                )
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $reservation->refresh();
        $book->refresh();

        $this->assertSame(
            BookReservation::STATUS_COLLECTED,
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->collected_at
        );

        $this->assertDatabaseHas('borrowings', [
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' => 'borrowed',
        ]);

        $this->assertSame(
            1,
            $book->available_copies
        );
    }

    public function test_approved_hold_protects_last_copy(): void
    {
        $librarian = User::factory()->create([
            'role' => User::ROLE_LIBRARIAN,
        ]);

        $reservedStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $otherStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $book = Book::query()->create([
            'title' => 'The Pragmatic Programmer',
            'author' => 'Andrew Hunt',
            'isbn' => fake()->unique()->isbn13(),
            'category' => 'Programming',
            'type' => Book::TYPE_PHYSICAL,
            'total_copies' => 1,
            'available_copies' => 1,
        ]);

        BookReservation::query()->create([
            'user_id' => $reservedStudent->id,
            'book_id' => $book->id,
            'status' => BookReservation::STATUS_APPROVED,
            'requested_at' => now()->subDay(),
            'reviewed_at' => now(),
            'reviewed_by' => $librarian->id,
            'expires_at' => now()->addDays(2),
        ]);

        $this
            ->actingAs($otherStudent)
            ->post(
                route('borrowings.store'),
                ['book_id' => $book->id]
            )
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('borrowings', [
            'user_id' => $otherStudent->id,
            'book_id' => $book->id,
        ]);

        $this->assertSame(
            1,
            $book->fresh()->available_copies
        );
    }
}