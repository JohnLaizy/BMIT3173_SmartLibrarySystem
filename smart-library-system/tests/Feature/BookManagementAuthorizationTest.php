<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_see_or_access_book_management_actions(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $book = $this->book();

        $this->actingAs($student)
            ->get(route('books.index'))
            ->assertOk()
            ->assertDontSee('New Book')
            ->assertDontSee('Edit')
            ->assertDontSee('Delete');

        $this->get(route('books.create'))->assertForbidden();
        $this->get(route('books.edit', $book))->assertForbidden();
        $this->put(route('books.update', $book), $this->payload())->assertForbidden();
    }

    public function test_librarian_can_open_and_update_a_book(): void
    {
        $librarian = User::factory()->create(['role' => User::ROLE_LIBRARIAN]);
        $book = $this->book();

        $this->actingAs($librarian)
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('New Book')
            ->assertSee(route('books.edit', $book), false);

        $this->get(route('books.edit', $book))
            ->assertOk()
            ->assertSee('Edit Book');

        $this->put(route('books.update', $book), $this->payload(['title' => 'Updated title']))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated title',
        ]);
    }

    public function test_non_book_route_value_returns_not_found_instead_of_a_type_error(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($student)
            ->get(route('books.show', 'edit'))
            ->assertNotFound();
    }

    private function book(): Book
    {
        return Book::query()->create([
            'isbn' => '9780132350884',
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'category' => 'Programming',
            'type' => Book::TYPE_PHYSICAL,
            'total_copies' => 2,
            'available_copies' => 2,
        ]);
    }

    /** @return array<string, int|string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'category' => 'Programming',
            'total_copies' => 2,
        ], $overrides);
    }
}
