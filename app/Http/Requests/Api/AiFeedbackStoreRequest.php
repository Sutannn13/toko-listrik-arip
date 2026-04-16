<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AiFeedbackStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'max:120'],
            'message_id' => ['nullable', 'string', 'max:120'],
            'intent' => ['nullable', 'string', 'max:50'],
            'rating' => ['required', 'integer', Rule::in([-1, 1])],
            'reason' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
            'metadata.provider' => ['nullable', 'string', 'max:50'],
            'metadata.model' => ['nullable', 'string', 'max:120'],
            'metadata.fallback_used' => ['nullable', 'boolean'],
            'metadata.status' => ['nullable', 'string', 'max:40'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validasi feedback AI gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
