<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGatewayStrategy;

class PublicBankPaymentStrategy implements PaymentGatewayStrategy
{
    public function key(): string
    {
        return 'public_bank';
    }

    public function label(): string
    {
        return 'Public Bank';
    }

    public function destinationUrl(): string
    {
        return (string) config(
            'library.simulated_payment_gateways.public_bank.url'
        );
    }
}
