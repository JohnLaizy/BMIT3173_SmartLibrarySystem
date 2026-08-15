<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulatedPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_a_simulated_bank_payment_for_librarian_approval(): void
    {
        $student = User::factory()->student()->create();
        $librarian = User::factory()->librarian()->create();

        $borrowing = $this->feeUnpaidBorrowingFor($student);

        $response = $this->actingAs($student)
            ->post(route('payments.start', $borrowing), [
                'payment_method' => 'maybank',
            ]);

        $response->assertRedirect(route('payments.show', $borrowing));

        $borrowing->refresh();

        $this->assertSame(
            Borrowing::STATUS_FEE_UNPAID,
            $borrowing->status
        );
        $this->assertSame('maybank', $borrowing->payment_method);
        $this->assertNotNull($borrowing->payment_started_at);
        $this->assertMatchesRegularExpression(
            '/^PAY-\d{8}-[A-Z0-9]{8}$/',
            (string) $borrowing->payment_reference
        );

        $this->assertDatabaseHas('payment_audits', [
            'borrowing_id' => $borrowing->id,
            'actor_user_id' => $student->id,
            'payment_reference' => $borrowing->payment_reference,
            'event' => 'simulation_started',
        ]);

        $this->actingAs($student)
            ->post(route('payments.complete', $borrowing), [
                'confirmed_simulation' => '1',
            ])
            ->assertRedirect(route('payments.index'));

        $borrowing->refresh();

        $this->assertSame(
            Borrowing::STATUS_PAYMENT_PENDING,
            $borrowing->status
        );
        $this->assertNotNull($borrowing->payment_submitted_at);

        $this->assertDatabaseHas('payment_audits', [
            'borrowing_id' => $borrowing->id,
            'actor_user_id' => $student->id,
            'payment_reference' => $borrowing->payment_reference,
            'event' => 'simulation_completed',
        ]);

        $this->actingAs($librarian)
            ->patch(route('borrowings.payment.approve', $borrowing))
            ->assertRedirect();

        $borrowing->refresh();

        $this->assertSame(
            Borrowing::STATUS_COMPLETED,
            $borrowing->status
        );
        $this->assertSame(
            $librarian->id,
            $borrowing->payment_approved_by
        );

        $this->assertDatabaseHas('payment_audits', [
            'borrowing_id' => $borrowing->id,
            'actor_user_id' => $librarian->id,
            'payment_reference' => $borrowing->payment_reference,
            'event' => 'payment_approved',
        ]);

        $this->actingAs($student)
            ->get(route('payments.receipt', $borrowing))
            ->assertOk()
            ->assertSee($borrowing->payment_reference)
            ->assertSee('Simulated payment receipt');
    }

    public function test_student_cannot_start_a_payment_for_another_students_fee(): void
    {
        $owner = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $borrowing = $this->feeUnpaidBorrowingFor($owner);

        $this->actingAs($otherStudent)
            ->post(route('payments.start', $borrowing), [
                'payment_method' => 'maybank',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('payment_audits', [
            'borrowing_id' => $borrowing->id,
        ]);
    }

    /**
     * Create a returned borrowing with a fee that still requires payment.
     */
    private function feeUnpaidBorrowingFor(User $student): Borrowing
    {
        $book = Book::query()->create([
            'isbn' => '978'.fake()->unique()->numerify('##########'),
            'title' => 'Payment Test Book',
            'author' => 'Smart Library',
            'category' => 'Testing',
            'type' => 'physical',
            'total_copies' => 1,
            'available_copies' => 1,
        ]);

        $borrowing = Borrowing::query()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_FEE_UNPAID,
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);

        $borrowing->forceFill([
            'returned_at' => now()->subDay(),
            'overdue_fee_cents' => 250,
        ])->save();

        return $borrowing;
    }
}
