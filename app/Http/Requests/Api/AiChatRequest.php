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
            'context' => ['nullable', 'array'],
            'context.locale' => ['nullable', 'string', 'max:10'],
            'context.channel' => ['nullable', 'string', 'max:50'],
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
