<?php

namespace App\Http\Requests;

use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;

class RejectBorrowingRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $borrowing = $this->route('borrowing');

        return $borrowing instanceof Borrowing
            && (
                $this->user()?->can(
                    'rejectRenewal',
                    $borrowing
                ) ?? false
            );
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input(
            'renewal_rejection_reason'
        );

        if (is_string($reason)) {
            $this->merge([
                'renewal_rejection_reason' =>
                    trim($reason),
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'renewal_rejection_reason' => [
                'bail',
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'renewal_rejection_reason.required' =>
                'A rejection reason is required.',

            'renewal_rejection_reason.max' =>
                'The rejection reason cannot exceed 255 characters.',
        ];
    }
}