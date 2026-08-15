<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGatewayStrategy;

class MaybankPaymentStrategy implements PaymentGatewayStrategy
{
    public function key(): string
    {
        return 'maybank';
    }

    public function label(): string
    {
        return 'Maybank2u';
    }

    public function destinationUrl(): string
    {
        return (string) config(
            'library.simulated_payment_gateways.maybank.url'
        );
    }
}
