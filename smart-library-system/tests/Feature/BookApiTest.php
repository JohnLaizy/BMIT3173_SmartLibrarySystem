<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_and_single_book_include_live_borrowing_availability(): void
    {
        $book = $this->book(['total_copies' => 10]);
        Http::fake(['*/borrowings/active-counts*' => Http::response(['success' => true, 'data' => [$book->id => 3]])]);

        $this->getJson('/api/v1/books')->assertOk()
            ->assertJsonPath('data.data.0.id', $book->id)
            ->assertJsonPath('data.data.0.borrowed_copies', 3)
            ->assertJsonPath('data.data.0.available_copies', 7);
        $this->getJson("/api/v1/books/{$book->id}")->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.borrowed_copies', 3)
            ->assertJsonPath('data.available_copies', 7);

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/borrowings/active-counts')
            && str_contains($request->url(), 'book_ids='.$book->id)
            && $request->hasHeader('Accept', 'application/json')
        );
    }

    public function test_book_mutations_require_authenticated_librarian(): void
    {
        $book = $this->book();
        $payload = $this->payload();
        $this->postJson('/api/v1/books', $payload)->assertUnauthorized();

        $this->actingAs(User::factory()->student()->create());
        $this->postJson('/api/v1/books', $payload)->assertForbidden();
        $this->putJson("/api/v1/books/{$book->id}", $this->updatePayload())->assertForbidden();
    }

    public function test_librarian_can_create_update_and_delete_book_through_api(): void
    {
        $this->actingAs(User::factory()->librarian()->create());
        Http::fake(['*/borrowings/active-counts*' => Http::response(['success' => true, 'data' => []])]);

        $created = $this->postJson('/api/v1/books', $this->payload())->assertCreated()->json('data.id');
        $this->patchJson("/api/v1/books/{$created}", $this->updatePayload(['title' => 'Updated API book']))
            ->assertOk()->assertJsonPath('data.title', 'Updated API book');
        $this->deleteJson("/api/v1/books/{$created}")->assertOk();
    }

    public function test_validation_and_missing_book_return_json_errors(): void
    {
        $this->actingAs(User::factory()->librarian()->create());
        $this->postJson('/api/v1/books', [])->assertUnprocessable()->assertJsonValidationErrors(['isbn', 'title']);
        $this->getJson('/api/v1/books/99999')->assertNotFound();
    }

    public function test_book_api_returns_safe_downstream_failure_responses(): void
    {
        $this->book();
        Http::fake(['*/borrowings/active-counts*' => Http::response(['data' => 'invalid'], 200)]);
        $this->getJson('/api/v1/books')->assertOk()
            ->assertJsonPath('data.data.0.active_borrowings_count', 0);

        Http::fake(fn () => throw new ConnectionException('timed out'));
        $this->getJson('/api/v1/books')->assertOk()
            ->assertJsonPath('data.data.0.availability_service_unavailable', true);
    }

    public function test_zero_active_borrowings_leave_all_physical_copies_available(): void
    {
        $book = $this->book(['total_copies' => 10]);
        Http::fake(['*/borrowings/active-counts*' => Http::response(['success' => true, 'data' => [$book->id => 0]])]);

        $this->getJson('/api/v1/books')->assertOk()
            ->assertJsonPath('data.data.0.borrowed_copies', 0)
            ->assertJsonPath('data.data.0.available_copies', 10)
            ->assertJsonPath('data.data.0.availability_service_unavailable', false);
    }

    public function test_all_copies_borrowed_reports_zero_available_copies(): void
    {
        $book = $this->book(['total_copies' => 1]);
        Http::fake(['*/borrowings/active-counts*' => Http::response(['success' => true, 'data' => [$book->id => 1]])]);

        $this->getJson('/api/v1/books')->assertOk()
            ->assertJsonPath('data.data.0.borrowed_copies', 1)
            ->assertJsonPath('data.data.0.available_copies', 0);
    }

    public function test_ebook_keeps_digital_access_in_the_book_management_view(): void
    {
        $this->withoutVite();
        $book = $this->book(['type' => Book::TYPE_EBOOK]);
        Http::fake(['*/borrowings/active-counts*' => Http::response(['success' => true, 'data' => []])]);

        $this->actingAs(User::factory()->librarian()->create())
            ->get('/books')
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee('Digital Access');
    }

    public function test_book_management_view_distinguishes_an_api_failure_from_zero_stock(): void
    {
        $this->withoutVite();
        $book = $this->book(['total_copies' => 10]);
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->actingAs(User::factory()->librarian()->create())
            ->get('/books')
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee('Availability temporarily unavailable')
            ->assertDontSee('>Unavailable<', false);
    }

    public function test_active_counts_endpoint_groups_active_borrowings_by_book_id(): void
    {
        $firstBook = $this->book();
        $secondBook = $this->book(['isbn' => '9780132350885']);
        $student = User::factory()->student()->create();

        Borrowing::factory()->count(2)->create([
            'book_id' => $firstBook->id,
            'user_id' => $student->id,
            'status' => Borrowing::STATUS_BORROWED,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(14),
            'returned_at' => null,
        ]);
        Borrowing::factory()->create([
            'book_id' => $secondBook->id,
            'user_id' => $student->id,
            'status' => Borrowing::STATUS_COMPLETED,
            'borrowed_at' => now()->subDays(14),
            'due_at' => now()->subDay(),
            'returned_at' => now(),
        ]);

        $this->getJson("/api/v1/borrowings/active-counts?book_ids={$firstBook->id},{$secondBook->id}")
            ->assertOk()
            ->assertJsonPath("data.{$firstBook->id}", 2)
            ->assertJsonPath("data.{$secondBook->id}", 0);
    }

    public function test_a_book_with_active_borrowings_cannot_be_deleted_but_another_can(): void
    {
        $this->actingAs(User::factory()->librarian()->create());
        $borrowedBook = $this->book();
        $availableBook = $this->book(['isbn' => '9780132350885']);

        Http::fake(['*/borrowings/active-counts*' => Http::response([
            'success' => true,
            'data' => [$borrowedBook->id => 1, $availableBook->id => 0],
        ])]);

        $this->deleteJson("/api/v1/books/{$borrowedBook->id}")->assertUnprocessable();
        $this->deleteJson("/api/v1/books/{$availableBook->id}")->assertOk();

        $this->assertDatabaseHas('books', ['id' => $borrowedBook->id]);
        $this->assertDatabaseMissing('books', ['id' => $availableBook->id]);
    }

    private function book(array $overrides = []): Book
    {
        return Book::query()->create(array_merge($this->payload(), ['available_copies' => 3], $overrides));
    }

    /** @return array<string, int|string> */
    private function payload(array $overrides = []): array
    {
        return array_merge(['isbn' => '9780132350884', 'title' => 'API Book', 'author' => 'Author', 'category' => 'Testing', 'type' => 'physical', 'total_copies' => 3], $overrides);
    }

    /** @return array<string, int|string> */
    private function updatePayload(array $overrides = []): array
    {
        return array_merge(['title' => 'API Book', 'author' => 'Author', 'category' => 'Testing', 'total_copies' => 3], $overrides);
    }
}
