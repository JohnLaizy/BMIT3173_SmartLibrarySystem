<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookBorrowingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ebook_cannot_enter_physical_borrowing_workflow(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $ebook = Book::query()->create([
            'isbn' => fake()->unique()->isbn13(),
            'title' => 'Digital Design Patterns',
            'author' => 'Example Author',
            'category' => 'Programming',
            'type' => Book::TYPE_EBOOK,
            'total_copies' => 1,
            'available_copies' => 1,
            'file_path' =>
                'ebooks_secured/example.pdf',
        ]);

        $this->actingAs($student)
            ->post(
                route('borrowings.store'),
                ['book_id' => $ebook->id]
            )
            ->assertSessionHasErrors('book_id');

        $this->assertDatabaseMissing('borrowings', [
            'user_id' => $student->id,
            'book_id' => $ebook->id,
        ]);
    }
}