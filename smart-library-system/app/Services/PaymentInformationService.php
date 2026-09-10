<?php

namespace App\Services;

use App\Models\Borrowing;

class PaymentInformationService
{
    public function getUserPaymentStatus(int $userId): array
    {
        $payments = Borrowing::query()
            ->where('user_id', $userId)
            ->where('overdue_fee_cents', '>', 0)
            ->get();

        $hasOutstandingPayment = $payments->contains(
            fn (Borrowing $borrowing) =>
                in_array(
                    $borrowing->status,
                    [
                        Borrowing::STATUS_FEE_UNPAID,
                        Borrowing::STATUS_PAYMENT_PENDING,
                    ],
                    true
                )
        );

        $hasPendingPayment = $payments->contains(
            fn (Borrowing $borrowing) =>
                $borrowing->status ===
                    Borrowing::STATUS_PAYMENT_PENDING
        );

        return [
            'user_id' => $userId,

            'has_outstanding_payment' =>
                $hasOutstandingPayment,

            'has_pending_payment' =>
                $hasPendingPayment,

            'payments' => $payments
                ->map(
                    fn (Borrowing $borrowing) => [
                        'borrowing_id' =>
                            $borrowing->id,

                        'amount_cents' =>
                            $borrowing->overdue_fee_cents,

                        'payment_status' =>
                            $borrowing->status,

                        'payment_reference' =>
                            $borrowing->payment_reference,

                        'payment_method' =>
                            $borrowing->payment_method,
                    ]
                )
                ->values()
                ->all(),
        ];
    }

    public function hasOutstandingPayment(int $userId): bool
    {
        $data = $this->getUserPaymentStatus($userId);

        return $data['has_outstanding_payment'];
    }
}