<?php

namespace Tests\Feature;

use App\Models\Book;
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
        $book = $this->book();
        Http::fake(['*/borrowings/active-counts*' => Http::response(['success' => true, 'data' => [$book->id => 1]])]);

        $this->getJson('/api/v1/books')->assertOk()
            ->assertJsonPath('data.data.0.id', $book->id)
            ->assertJsonPath('data.data.0.active_borrowings_count', 1);
        $this->getJson("/api/v1/books/{$book->id}")->assertOk()
            ->assertJsonPath('data.id', $book->id);

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
            ->assertJsonPath('data.data.0.active_borrowings_count', 0);
    }

    private function book(): Book
    {
        return Book::query()->create(array_merge($this->payload(), ['available_copies' => 3]));
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
