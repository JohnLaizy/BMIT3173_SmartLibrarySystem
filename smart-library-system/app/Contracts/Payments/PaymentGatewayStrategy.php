<?php

namespace App\Contracts\Payments;

/**
 * Strategy Pattern contract for a simulated online-banking destination.
 *
 * A strategy only supplies public display data and a public bank URL. It does
 * not call a bank API and must never collect bank login credentials.
 */
interface PaymentGatewayStrategy
{
    public function key(): string;

    public function label(): string;

    public function destinationUrl(): string;
}
