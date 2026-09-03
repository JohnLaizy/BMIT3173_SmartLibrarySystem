<?php

namespace Tests\Feature;

use App\Contracts\UserManagementPort;
use App\Exceptions\BorrowingRuleViolation;
use App\Integrations\BookManagement\JsonBookManagementAdapter;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Services\BorrowingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BorrowingBookManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $bookJsonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $path = tempnam(
            sys_get_temp_dir(),
            'book-management-'
        );

        if ($path === false) {
            throw new RuntimeException(
                'Could not create temporary JSON file.'
            );
        }

        $this->bookJsonPath = $path;
    }

    protected function tearDown(): void
    {
        if (
            isset($this->bookJsonPath)
            && file_exists($this->bookJsonPath)
        ) {
            unlink($this->bookJsonPath);
        }

        parent::tearDown();
    }

    public function test_valid_book_data_allows_borrowing(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $book = $this->createBook();

        $this->writeBooks([
            $this->apiBook($book),
        ]);

        $service = $this->createService($student);

        $borrowing = $service->borrow(
            $student,
            $book
        );

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_BORROWED,
        ]);
    }

    public function test_successful_borrow_decrements_both_inventories(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $book = $this->createBook();

        $this->writeBooks([
            $this->apiBook($book),
        ]);

        $service = $this->createService($student);

        $service->borrow($student, $book);

        $this->assertSame(
            1,
            $book->fresh()->available_copies
        );

        $this->assertSame(
            1,
            $this->readFirstBook()['available_copies']
        );
    }

    public function test_book_missing_from_json_blocks_borrowing(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $book = $this->createBook();

        $this->writeBooks([]);

        $service = $this->createService($student);

        $this->assertRuleViolation(
            'The book could not be verified by Book Management.',
            fn () => $service->borrow(
                $student,
                $book
            )
        );

        $this->assertBorrowDidNotChangeDatabase($book);
    }

    public function test_non_borrowable_json_book_blocks_borrowing(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $book = $this->createBook();

        $this->writeBooks([
            $this->apiBook($book, [
                'borrowable' => false,
            ]),
        ]);

        $service = $this->createService($student);

        $this->assertRuleViolation(
            'Book Management does not allow this book to be borrowed.',
            fn () => $service->borrow(
                $student,
                $book
            )
        );

        $this->assertBorrowDidNotChangeDatabase($book);

        $this->assertSame(
            2,
            $this->readFirstBook()['available_copies']
        );
    }

    public function test_zero_json_copies_blocks_borrowing(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $book = $this->createBook();

        $this->writeBooks([
            $this->apiBook($book, [
                'available_copies' => 0,
            ]),
        ]);

        $service = $this->createService($student);

        $this->assertRuleViolation(
            'Book Management reports that no copy is currently available.',
            fn () => $service->borrow(
                $student,
                $book
            )
        );

        $this->assertBorrowDidNotChangeDatabase($book);

        $this->assertSame(
            0,
            $this->readFirstBook()['available_copies']
        );
    }

    public function test_successful_return_increments_both_inventories(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $book = $this->createBook();

        $this->writeBooks([
            $this->apiBook($book),
        ]);

        $service = $this->createService($student);

        $borrowing = $service->borrow(
            $student,
            $book
        );

        $this->assertSame(
            1,
            $book->fresh()->available_copies
        );

        $this->assertSame(
            1,
            $this->readFirstBook()['available_copies']
        );

        $returnedBorrowing = $service->returnCopy(
            $student,
            $borrowing
        );

        $this->assertNotNull(
            $returnedBorrowing->returned_at
        );

        $this->assertSame(
            2,
            $book->fresh()->available_copies
        );

        $this->assertSame(
            2,
            $this->readFirstBook()['available_copies']
        );
    }

    public function test_missing_user_blocks_before_database_mutation(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $book = $this->createBook();

        $this->writeBooks([
            $this->apiBook($book),
        ]);

        $service = $this->createService(
            $student,
            userExists: false
        );

        $this->assertRuleViolation(
            'The user could not be verified by User Management.',
            fn () => $service->borrow(
                $student,
                $book
            )
        );

        $this->assertBorrowDidNotChangeDatabase($book);

        $this->assertSame(
            2,
            $this->readFirstBook()['available_copies']
        );
    }

    private function createService(
        User $student,
        bool $userExists = true
    ): BorrowingService {
        $userData = $userExists
            ? [
                'user_id' => (string) $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'account_status' => 'ACTIVE',
                'borrowing_allowed' => true,
            ]
            : null;

        return new BorrowingService(
            new StubUserManagementPort($userData),
            new JsonBookManagementAdapter(
                $this->bookJsonPath
            )
        );
    }

    private function createBook(): Book
    {
        return Book::query()->create([
            'isbn' => fake()->unique()->isbn13(),
            'title' => 'Integration Test Book',
            'author' => 'Test Author',
            'category' => 'Testing',
            'type' => Book::TYPE_PHYSICAL,
            'total_copies' => 2,
            'available_copies' => 2,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function apiBook(
        Book $book,
        array $overrides = []
    ): array {
        return array_replace([
            'book_id' => (string) $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'type' => 'PHYSICAL',
            'total_copies' => 2,
            'available_copies' => 2,
            'borrowable' => true,
        ], $overrides);
    }

    /**
     * @param array<int, array<string, mixed>> $books
     */
    private function writeBooks(array $books): void
    {
        $json = json_encode(
            $books,
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        );

        file_put_contents(
            $this->bookJsonPath,
            $json
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readFirstBook(): array
    {
        $json = file_get_contents(
            $this->bookJsonPath
        );

        if ($json === false) {
            throw new RuntimeException(
                'Could not read temporary JSON file.'
            );
        }

        $books = json_decode(
            $json,
            true,
            flags: JSON_THROW_ON_ERROR
        );

        return $books[0];
    }

    private function assertRuleViolation(
        string $expectedMessage,
        callable $operation
    ): void {
        try {
            $operation();

            $this->fail(
                'Expected a BorrowingRuleViolation.'
            );
        } catch (BorrowingRuleViolation $exception) {
            $this->assertSame(
                $expectedMessage,
                $exception->getMessage()
            );
        }
    }

    private function assertBorrowDidNotChangeDatabase(
        Book $book
    ): void {
        $this->assertDatabaseCount(
            'borrowings',
            0
        );

        $this->assertSame(
            2,
            $book->fresh()->available_copies
        );
    }
}

final class StubUserManagementPort implements UserManagementPort
{
    /**
     * @param array<string, mixed>|null $userData
     */
    public function __construct(
        private ?array $userData
    ) {
    }

    public function getUser(
        string $userId
    ): ?array {
        if ($this->userData === null) {
            return null;
        }

        if (
            (string) (
                $this->userData['user_id'] ?? ''
            ) !== $userId
        ) {
            return null;
        }

        return $this->userData;
    }
}