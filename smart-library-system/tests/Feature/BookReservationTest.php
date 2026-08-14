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
            'total_copies' => 2,
            'available_copies' => 2,
        ]);
    }
}