<?php

namespace App\Http\Requests;

use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;

class BorrowBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Borrowing::class
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $bookId = $this->input('book_id');

        if (is_string($bookId)) {
            $this->merge([
                'book_id' => trim($bookId),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'book_id' => [
                'bail',
                'required',
                'integer',
                'exists:books,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'Please select a book.',

            'book_id.integer' => 'The selected book is invalid.',

            'book_id.exists' => 'The selected book could not be found.',
        ];
    }
}
