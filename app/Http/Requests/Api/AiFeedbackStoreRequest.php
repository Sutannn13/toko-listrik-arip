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
            'intent_detected' => ['nullable', 'string', 'max:50'],
            'intent_resolved' => ['nullable', 'string', 'max:50'],
            'rating' => ['required', 'integer', Rule::in([-1, 1])],
            'reason' => ['nullable', 'string', 'max:500'],
            'reason_code' => ['nullable', 'string', 'max:64'],
            'reason_detail' => ['nullable', 'string', 'max:2000'],
            'provider' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:120'],
            'llm_status' => ['nullable', 'string', 'max:40'],
            'fallback_used' => ['nullable', 'boolean'],
            'response_latency_ms' => ['nullable', 'integer', 'min:0', 'max:120000'],
            'prompt_version' => ['nullable', 'string', 'max:64'],
            'rule_version' => ['nullable', 'string', 'max:64'],
            'response_source' => ['nullable', 'string', 'max:30'],
            'feedback_version' => ['nullable', 'integer', 'min:1', 'max:10'],
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
