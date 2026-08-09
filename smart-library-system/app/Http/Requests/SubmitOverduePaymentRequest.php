<?php

namespace App\Http\Requests;

use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;

class SubmitOverduePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $borrowing = $this->route('borrowing');

        return $borrowing instanceof Borrowing
            && (
                $this->user()?->can(
                    'submitPayment',
                    $borrowing
                ) ?? false
            );
    }

    protected function prepareForValidation(): void
    {
        $reference = $this->input(
            'payment_reference'
        );

        if (is_string($reference)) {
            $this->merge([
                'payment_reference' =>
                    trim($reference),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'payment_reference' => [
                'bail',
                'required',
                'string',
                'min:6',
                'max:100',
                'regex:/^[A-Za-z0-9][A-Za-z0-9 _-]*$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_reference.required' =>
                'A payment reference is required.',

            'payment_reference.min' =>
                'The payment reference must contain at least 6 characters.',

            'payment_reference.max' =>
                'The payment reference cannot exceed 100 characters.',

            'payment_reference.regex' =>
                'The payment reference may contain only letters, numbers, spaces, hyphens and underscores.',
        ];
    }
}