<?php

namespace App\Services;

use App\Exceptions\BorrowingRuleViolation;
use App\Models\Borrowing;
use App\Models\PaymentAudit;
use App\Models\User;
use App\Services\Payments\SimulatedPaymentGatewayResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Coordinates an explicitly simulated online-banking workflow.
 *
 * No external bank API, credentials, account details, callback or payment
 * verification is implemented here. A librarian must approve the student's
 * completed simulation before the overdue fee is treated as resolved.
 */
class SimulatedPaymentService
{
    public function __construct(
        private SimulatedPaymentGatewayResolver $gatewayResolver
    ) {}

    public function start(
        User $student,
        Borrowing $borrowing,
        string $paymentMethod
    ): Borrowing {
        $gateway = $this->gatewayResolver->resolve($paymentMethod);

        return DB::transaction(
            function () use ($student, $borrowing, $gateway): Borrowing {
                $lockedBorrowing = $this->lockedStudentFee(
                    $student,
                    $borrowing
                );

                $reference = $this->generateReference();

                $lockedBorrowing->forceFill([
                    'payment_reference' => $reference,
                    'payment_method' => $gateway->key(),
                    'payment_started_at' => now(),
                ]);

                $lockedBorrowing->save();

                PaymentAudit::query()->create([
                    'borrowing_id' => $lockedBorrowing->id,
                    'actor_user_id' => $student->id,
                    'payment_reference' => $reference,
                    'event' => 'simulation_started',
                    'metadata' => [
                        'payment_method' => $gateway->key(),
                        'gateway_label' => $gateway->label(),
                    ],
                ]);

                Log::info('Simulated payment started.', [
                    'borrowing_id' => $lockedBorrowing->id,
                    'user_id' => $student->id,
                    'payment_reference' => $reference,
                    'payment_method' => $gateway->key(),
                ]);

                return $lockedBorrowing;
            },
            attempts: 3
        );
    }

    public function complete(
        User $student,
        Borrowing $borrowing
    ): Borrowing {
        return DB::transaction(
            function () use ($student, $borrowing): Borrowing {
                $lockedBorrowing = $this->lockedStudentFee(
                    $student,
                    $borrowing
                );

                if (
                    $lockedBorrowing->payment_reference === null
                    || $lockedBorrowing->payment_method === null
                    || $lockedBorrowing->payment_started_at === null
                ) {
                    throw BorrowingRuleViolation::because(
                        'Choose a payment method before confirming the simulation.'
                    );
                }

                $lockedBorrowing->state()->submitPayment(
                    $lockedBorrowing->payment_reference,
                    now()
                );

                $lockedBorrowing->save();

                PaymentAudit::query()->create([
                    'borrowing_id' => $lockedBorrowing->id,
                    'actor_user_id' => $student->id,
                    'payment_reference' => $lockedBorrowing->payment_reference,
                    'event' => 'simulation_completed',
                    'metadata' => [
                        'payment_method' => $lockedBorrowing->payment_method,
                        'verification_required' => true,
                    ],
                ]);

                Log::info('Simulated payment submitted for approval.', [
                    'borrowing_id' => $lockedBorrowing->id,
                    'user_id' => $student->id,
                    'payment_reference' => $lockedBorrowing->payment_reference,
                ]);

                return $lockedBorrowing;
            },
            attempts: 3
        );
    }

    private function lockedStudentFee(
        User $student,
        Borrowing $borrowing
    ): Borrowing {
        if (! $student->isStudent()) {
            throw BorrowingRuleViolation::because(
                'Only students may complete a simulated payment.'
            );
        }

        $lockedBorrowing = Borrowing::query()
            ->lockForUpdate()
            ->findOrFail($borrowing->id);

        if ($lockedBorrowing->user_id !== $student->id) {
            throw BorrowingRuleViolation::because(
                'You may pay only your own overdue fee.'
            );
        }

        if (
            $lockedBorrowing->status
            !== Borrowing::STATUS_FEE_UNPAID
        ) {
            throw BorrowingRuleViolation::because(
                'This overdue fee is no longer available for payment.'
            );
        }

        if ($lockedBorrowing->overdue_fee_cents < 1) {
            throw BorrowingRuleViolation::because(
                'There is no overdue fee to pay for this borrowing.'
            );
        }

        return $lockedBorrowing;
    }

    private function generateReference(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $reference = sprintf(
                'PAY-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(8))
            );

            $exists = Borrowing::query()
                ->where('payment_reference', $reference)
                ->exists();

            if (! $exists) {
                return $reference;
            }
        }

        throw BorrowingRuleViolation::because(
            'Unable to generate a unique payment reference. Please try again.'
        );
    }
}
