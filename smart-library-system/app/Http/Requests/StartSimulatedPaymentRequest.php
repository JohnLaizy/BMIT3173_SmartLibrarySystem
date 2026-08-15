<?php

namespace App\Http\Requests;

use App\Models\Borrowing;
use App\Services\Payments\SimulatedPaymentGatewayResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartSimulatedPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $borrowing = $this->route('borrowing');

        return $borrowing instanceof Borrowing
            && ($this->user()?->can('submitPayment', $borrowing) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var SimulatedPaymentGatewayResolver $gatewayResolver */
        $gatewayResolver = app(SimulatedPaymentGatewayResolver::class);

        return [
            'payment_method' => [
                'bail',
                'required',
                'string',
                Rule::in($gatewayResolver->allowedKeys()),
            ],
        ];
    }
}
