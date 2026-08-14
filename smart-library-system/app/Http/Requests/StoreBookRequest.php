<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 配合 Policy 或 Role 权限校验
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => ['required', 'string', 'max:17', 'regex:/^(?=(?:\D*\d){10}(?:(?:\D*\d){3})?$)[\d-]+$/', 'unique:books,isbn'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['physical', 'ebook'])],
            'total_copies' => ['required_if:type,physical', 'integer', 'min:1', 'max:500'],
            
            // 安全限制：仅允许业务所需的 JPG, PNG, WEBP 图片，限制 2MB
            'cover_image' => ['nullable', 'file', 'mimes:jpeg,png,webp', 'max:2048'],
            
            // 电子书文件限制：仅允许 PDF / EPUB，限制 20MB
            'ebook_file' => ['required_if:type,ebook', 'nullable', 'file', 'mimes:pdf,epub', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.regex' => 'The ISBN format is invalid. Must be valid ISBN-10 or ISBN-13.',
            'cover_image.mimes' => 'Cover image must be a valid file of type: jpeg, png, webp.',
            'ebook_file.mimes' => 'E-Book must be a valid PDF or EPUB document.',
        ];
    }
}