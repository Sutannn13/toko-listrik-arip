<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:2', 'max:2000'],
            'order_code' => ['nullable', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone_last4' => ['nullable', 'digits:4'],
            'budget_max' => ['nullable', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:100'],
            'history' => ['nullable', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'min:1', 'max:500'],
            'context' => ['nullable', 'array'],
            'context.locale' => ['nullable', 'string', 'max:10'],
            'context.channel' => ['nullable', 'string', 'max:50'],
            'context.page_title' => ['nullable', 'string', 'max:180'],
            'context.page_path' => ['nullable', 'string', 'max:255'],
            'context.product_name' => ['nullable', 'string', 'max:160'],
            'context.product_description' => ['nullable', 'string', 'max:2000'],
            'context.product_keywords' => ['nullable', 'array'],
            'context.product_keywords.*' => ['nullable', 'string', 'max:80'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validasi chat AI gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
