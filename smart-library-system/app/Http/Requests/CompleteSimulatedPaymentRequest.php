<?php

namespace App\Http\Requests;

use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;

class CompleteSimulatedPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $borrowing = $this->route('borrowing');

        return $borrowing instanceof Borrowing
            && ($this->user()?->can('submitPayment', $borrowing) ?? false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'confirmed_simulation' => [
                'accepted',
            ],
        ];
    }
}
