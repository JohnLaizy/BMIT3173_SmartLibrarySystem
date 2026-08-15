<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGatewayStrategy;

class CimbPaymentStrategy implements PaymentGatewayStrategy
{
    public function key(): string
    {
        return 'cimb';
    }

    public function label(): string
    {
        return 'CIMB Clicks';
    }

    public function destinationUrl(): string
    {
        return (string) config(
            'library.simulated_payment_gateways.cimb.url'
        );
    }
}
