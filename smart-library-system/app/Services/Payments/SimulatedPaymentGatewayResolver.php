<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGatewayStrategy;
use InvalidArgumentException;

/**
 * Resolves the selected payment method to its Strategy implementation.
 */
class SimulatedPaymentGatewayResolver
{
    /**
     * @return array<int, PaymentGatewayStrategy>
     */
    public function all(): array
    {
        return [
            new MaybankPaymentStrategy,
            new CimbPaymentStrategy,
            new PublicBankPaymentStrategy,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function allowedKeys(): array
    {
        return array_map(
            fn (PaymentGatewayStrategy $strategy): string => $strategy->key(),
            $this->all()
        );
    }

    public function resolve(string $key): PaymentGatewayStrategy
    {
        foreach ($this->all() as $strategy) {
            if ($strategy->key() === $key) {
                return $strategy;
            }
        }

        throw new InvalidArgumentException(
            'The selected payment method is not supported.'
        );
    }
}
