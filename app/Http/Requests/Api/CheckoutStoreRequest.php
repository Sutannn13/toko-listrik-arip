<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CheckoutStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', 'string', Rule::in(['cod', 'bank_transfer', 'ewallet', 'dummy'])],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:99'],

            'address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where(fn($query) => $query->where('user_id', $this->user()?->id)),
            ],

            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'address_label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'address_phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address_notes' => ['nullable', 'string', 'max:1000'],
            'set_as_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.array' => 'Format items tidak valid.',
            'items.min' => 'Minimal ada 1 item untuk checkout.',
            'items.*.product_id.required_with' => 'product_id wajib diisi untuk setiap item.',
            'items.*.quantity.required_with' => 'quantity wajib diisi untuk setiap item.',
            'address_id.exists' => 'Alamat yang dipilih tidak valid untuk akun Anda.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $this->validateItemsSource($validator);
            $this->validateAddressInput($validator);
        });
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Anda tidak memiliki akses untuk melakukan checkout.',
        ], 403));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validasi checkout gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }

    private function validateItemsSource(\Illuminate\Validation\Validator $validator): void
    {
        $items = $this->input('items');
        $sessionCart = $this->session()->get('simple_cart', []);

        $hasItemsPayload = is_array($items) && count($items) > 0;
        $hasSessionCart = is_array($sessionCart) && count($sessionCart) > 0;

        if (! $hasItemsPayload && ! $hasSessionCart) {
            $validator->errors()->add('items', 'Keranjang kosong. Kirim items pada payload atau isi simple_cart di session.');
        }
    }

    private function validateAddressInput(\Illuminate\Validation\Validator $validator): void
    {
        $hasAddressId = filled($this->input('address_id'));
        $hasAnyNewAddressInput = $this->hasAnyNewAddressInput();

        if (! $hasAddressId && $hasAnyNewAddressInput) {
            $requiredFields = [
                'recipient_name',
                'address_phone',
                'address_line',
                'city',
                'province',
                'postal_code',
            ];

            foreach ($requiredFields as $requiredField) {
                if (blank($this->input($requiredField))) {
                    $validator->errors()->add($requiredField, 'Field ini wajib diisi saat membuat alamat baru.');
                }
            }

            return;
        }

        $user = $this->user();
        if (! $hasAddressId && ! $hasAnyNewAddressInput && $user && ! $user->addresses()->exists()) {
            $validator->errors()->add('address_id', 'Pilih alamat yang sudah ada atau isi data alamat baru.');
        }
    }

    private function hasAnyNewAddressInput(): bool
    {
        $fields = [
            'address_label',
            'recipient_name',
            'address_phone',
            'address_line',
            'city',
            'province',
            'postal_code',
            'address_notes',
        ];

        foreach ($fields as $field) {
            if (filled($this->input($field))) {
                return true;
            }
        }

        return false;
    }
}
