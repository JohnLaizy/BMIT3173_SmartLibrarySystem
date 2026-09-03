<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowingRenewalTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_an_extension(): void
    {
        $student = User::factory()->student()->create();
        $borrowing = $this->borrowingFor($student);

        $this->actingAs($student)
            ->post(route(
                'borrowings.renewal.request',
                $borrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('success');

        $borrowing->refresh();

        $this->assertSame(
            Borrowing::RENEWAL_STATUS_PENDING,
            $borrowing->renewal_status
        );
        $this->assertNotNull(
            $borrowing->renewal_requested_at
        );
        $this->assertNull(
            $borrowing->renewal_reviewed_at
        );
    }

    public function test_other_student_cannot_request_extension(): void
    {
        $owner = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $borrowing = $this->borrowingFor($owner);

        $this->actingAs($otherStudent)
            ->post(route(
                'borrowings.renewal.request',
                $borrowing
            ))
            ->assertForbidden();

        $this->assertNull(
            $borrowing->fresh()->renewal_status
        );
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        $student = User::factory()->student()->create();

        $borrowing = $this->borrowingFor($student, [
            'renewal_status' =>
                Borrowing::RENEWAL_STATUS_PENDING,
            'renewal_requested_at' => now()->subHour(),
        ]);

        $this->actingAs($student)
            ->post(route(
                'borrowings.renewal.request',
                $borrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(
            Borrowing::RENEWAL_STATUS_PENDING,
            $borrowing->fresh()->renewal_status
        );
    }

    public function test_overdue_borrowing_cannot_be_renewed(): void
    {
        $student = User::factory()->student()->create();

        // Status has not yet been updated by the scheduled command,
        // but the due date has already passed.
        $borrowing = $this->borrowingFor($student, [
            'status' => Borrowing::STATUS_BORROWED,
            'due_at' => now()->subMinute(),
        ]);

        $this->actingAs($student)
            ->post(route(
                'borrowings.renewal.request',
                $borrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(
            $borrowing->fresh()->renewal_status
        );
    }

    public function test_returned_borrowing_cannot_be_renewed(): void
    {
        $student = User::factory()->student()->create();

        $borrowing = $this->borrowingFor($student, [
            'status' => Borrowing::STATUS_COMPLETED,
            'returned_at' => now(),
        ]);

        $this->actingAs($student)
            ->post(route(
                'borrowings.renewal.request',
                $borrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(
            $borrowing->fresh()->renewal_status
        );
    }

    public function test_maximum_renewal_count_is_enforced(): void
    {
        config()->set('library.max_renewals', 1);

        $student = User::factory()->student()->create();

        $borrowing = $this->borrowingFor($student, [
            'renewal_status' =>
                Borrowing::RENEWAL_STATUS_APPROVED,
            'renewal_count' => 1,
        ]);

        $this->actingAs($student)
            ->post(route(
                'borrowings.renewal.request',
                $borrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_unresolved_overdue_record_blocks_renewal(): void
    {
        $student = User::factory()->student()->create();
        $activeBorrowing = $this->borrowingFor($student);

        $this->borrowingFor($student, [
            'status' => Borrowing::STATUS_FEE_UNPAID,
            'due_at' => now()->subDays(3),
            'returned_at' => now()->subDay(),
            'overdue_fee_cents' => 300,
        ]);

        $this->actingAs($student)
            ->post(route(
                'borrowings.renewal.request',
                $activeBorrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(
            $activeBorrowing->fresh()->renewal_status
        );
    }

    public function test_approved_reservation_for_another_student_blocks_renewal(): void
    {
        $student = User::factory()->student()->create();
        $reservedStudent = User::factory()->student()->create();
        $book = $this->createBook();

        $borrowing = $this->borrowingFor(
            $student,
            book: $book
        );

        $this->approvedReservation(
            $reservedStudent,
            $book
        );

        $this->actingAs($student)
            ->post(route(
                'borrowings.renewal.request',
                $borrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_approval_extends_due_date_and_increments_count(): void
    {
        config()->set(
            'library.renewal_extension_days',
            5
        );

        $student = User::factory()->student()->create();
        $librarian = User::factory()->librarian()->create();

        $borrowing = $this->borrowingFor($student, [
            'renewal_status' =>
                Borrowing::RENEWAL_STATUS_PENDING,
            'renewal_requested_at' => now()->subHour(),
            'renewal_count' => 0,
        ]);

        $originalDueAt = $borrowing->due_at;

        $this->actingAs($librarian)
            ->patch(route(
                'borrowings.renewal.approve',
                $borrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('success');

        $borrowing->refresh();

        $this->assertSame(
            Borrowing::RENEWAL_STATUS_APPROVED,
            $borrowing->renewal_status
        );
        $this->assertSame(1, $borrowing->renewal_count);
        $this->assertSame(
            $originalDueAt->addDays(5)->getTimestamp(),
            $borrowing->due_at->getTimestamp()
        );
        $this->assertSame(
            $librarian->id,
            $borrowing->renewal_reviewed_by
        );
    }

    public function test_rejection_stores_reviewer_time_and_reason(): void
    {
        $student = User::factory()->student()->create();
        $librarian = User::factory()->librarian()->create();

        $borrowing = $this->borrowingFor($student, [
            'renewal_status' =>
                Borrowing::RENEWAL_STATUS_PENDING,
            'renewal_requested_at' => now()->subHour(),
        ]);

        $this->actingAs($librarian)
            ->patch(
                route(
                    'borrowings.renewal.reject',
                    $borrowing
                ),
                [
                    'renewal_rejection_reason' =>
                        '  Reserved by another student.  ',
                ]
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $borrowing->refresh();

        $this->assertSame(
            Borrowing::RENEWAL_STATUS_REJECTED,
            $borrowing->renewal_status
        );
        $this->assertSame(
            $librarian->id,
            $borrowing->renewal_reviewed_by
        );
        $this->assertNotNull(
            $borrowing->renewal_reviewed_at
        );
        $this->assertSame(
            'Reserved by another student.',
            $borrowing->renewal_rejection_reason
        );
    }

    public function test_rejection_reason_is_required(): void
    {
        $student = User::factory()->student()->create();
        $librarian = User::factory()->librarian()->create();

        $borrowing = $this->borrowingFor($student, [
            'renewal_status' =>
                Borrowing::RENEWAL_STATUS_PENDING,
            'renewal_requested_at' => now()->subHour(),
        ]);

        $this->actingAs($librarian)
            ->patch(route(
                'borrowings.renewal.reject',
                $borrowing
            ))
            ->assertSessionHasErrors(
                'renewal_rejection_reason'
            );

        $this->assertSame(
            Borrowing::RENEWAL_STATUS_PENDING,
            $borrowing->fresh()->renewal_status
        );
    }

    public function test_students_cannot_approve_or_reject_renewals(): void
    {
        $student = User::factory()->student()->create();

        $borrowing = $this->borrowingFor($student, [
            'renewal_status' =>
                Borrowing::RENEWAL_STATUS_PENDING,
            'renewal_requested_at' => now(),
        ]);

        $this->actingAs($student)
            ->patch(route(
                'borrowings.renewal.approve',
                $borrowing
            ))
            ->assertForbidden();

        $this->actingAs($student)
            ->patch(
                route(
                    'borrowings.renewal.reject',
                    $borrowing
                ),
                [
                    'renewal_rejection_reason' =>
                        'Not permitted.',
                ]
            )
            ->assertForbidden();

        $this->assertSame(
            Borrowing::RENEWAL_STATUS_PENDING,
            $borrowing->fresh()->renewal_status
        );
    }

    public function test_approval_rechecks_eligibility(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $librarian = User::factory()->librarian()->create();
        $book = $this->createBook();

        $borrowing = $this->borrowingFor(
            $student,
            book: $book
        );

        $originalDueAt = $borrowing->due_at;

        $this->actingAs($student)
            ->post(route(
                'borrowings.renewal.request',
                $borrowing
            ))
            ->assertSessionHas('success');

        // This condition appeared after the student requested renewal.
        $this->approvedReservation(
            $otherStudent,
            $book
        );

        $this->actingAs($librarian)
            ->patch(route(
                'borrowings.renewal.approve',
                $borrowing
            ))
            ->assertRedirect()
            ->assertSessionHas('error');

        $borrowing->refresh();

        $this->assertSame(
            Borrowing::RENEWAL_STATUS_PENDING,
            $borrowing->renewal_status
        );
        $this->assertSame(0, $borrowing->renewal_count);
        $this->assertSame(
            $originalDueAt->getTimestamp(),
            $borrowing->due_at->getTimestamp()
        );
    }

    private function borrowingFor(
        User $student,
        array $overrides = [],
        ?Book $book = null
    ): Borrowing {
        $book ??= $this->createBook();

        $borrowing = new Borrowing();

        $borrowing->forceFill(array_replace([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_BORROWED,
            'borrowed_at' => now()->subDay(),
            'due_at' => now()->addDays(6),
            'returned_at' => null,
            'overdue_fee_cents' => 0,
            'renewal_status' => null,
            'renewal_requested_at' => null,
            'renewal_reviewed_at' => null,
            'renewal_reviewed_by' => null,
            'renewal_rejection_reason' => null,
            'renewal_count' => 0,
        ], $overrides));

        $borrowing->save();

        return $borrowing;
    }

    private function createBook(): Book
    {
        return Book::query()->create([
            'isbn' =>
                '978'.fake()->unique()->numerify('##########'),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'category' => 'Testing',
            'type' => Book::TYPE_PHYSICAL,
            'total_copies' => 2,
            'available_copies' => 1,
        ]);
    }

    private function approvedReservation(
        User $student,
        Book $book
    ): BookReservation {
        return BookReservation::query()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' =>
                BookReservation::STATUS_APPROVED,
            'requested_at' => now()->subDay(),
            'reviewed_at' => now(),
            'expires_at' => now()->addDays(2),
        ]);
    }
}