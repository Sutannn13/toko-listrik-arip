<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutStoreRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminNewOrderNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\Notification as NotificationMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function store(CheckoutStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            return response()->json([
                'message' => 'Autentikasi tidak valid.',
            ], 401);
        }

        $checkoutItems = $this->resolveCheckoutItems(
            $validated['items'] ?? null,
            $request->session()->get('simple_cart', []),
        );

        if (count($checkoutItems) === 0) {
            return response()->json([
                'message' => 'Validasi checkout gagal.',
                'errors' => [
                    'items' => ['Keranjang kosong. Tidak ada item untuk diproses.'],
                ],
            ], 422);
        }

        $paymentMethod = (string) ($validated['payment_method'] ?? 'dummy');

        try {
            [$order, $payment] = DB::transaction(function () use ($authenticatedUser, $validated, $checkoutItems, $paymentMethod) {
                $orderItemsPayload = [];
                $subtotalAmount = 0;
                $totalQuantity = 0;

                foreach ($checkoutItems as $productId => $quantity) {
                    $lockedProduct = Product::query()
                        ->whereKey($productId)
                        ->lockForUpdate()
                        ->first();

                    if (! $lockedProduct || ! $lockedProduct->is_active) {
                        throw ValidationException::withMessages([
                            'items' => ['Produk #' . $productId . ' tidak tersedia atau sudah nonaktif.'],
                        ]);
                    }

                    if ((int) $lockedProduct->stock < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => ['Stok produk ' . $lockedProduct->name . ' tidak mencukupi.'],
                        ]);
                    }

                    $lineSubtotal = ((int) $lockedProduct->price) * $quantity;
                    $subtotalAmount += $lineSubtotal;
                    $totalQuantity += $quantity;

                    $orderItemsPayload[] = [
                        'product' => $lockedProduct,
                        'quantity' => $quantity,
                        'line_subtotal' => $lineSubtotal,
                    ];
                }

                $shippingCost = $this->calculateShippingCost($totalQuantity);
                $discountAmount = 0;
                $totalAmount = $subtotalAmount + $shippingCost - $discountAmount;

                $address = $this->resolveCheckoutAddress($authenticatedUser, $validated);

                $order = Order::create([
                    'order_code' => $this->generateOrderCode(),
                    'user_id' => $authenticatedUser->id,
                    'address_id' => $address->id,
                    'customer_name' => (string) $validated['customer_name'],
                    'customer_email' => (string) $validated['customer_email'],
                    'customer_phone' => (string) $validated['customer_phone'],
                    'notes' => $this->buildOrderNotes($totalQuantity, $shippingCost, $validated, $address),
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'warranty_status' => 'active',
                    'subtotal' => $subtotalAmount,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'placed_at' => now(),
                ]);

                foreach ($orderItemsPayload as $itemPayload) {
                    /** @var Product $payloadProduct */
                    $payloadProduct = $itemPayload['product'];
                    $quantity = $itemPayload['quantity'];
                    $lineSubtotal = $itemPayload['line_subtotal'];

                    $warrantyDays = $payloadProduct->is_electronic ? 7 : 0;

                    $order->items()->create([
                        'product_id' => $payloadProduct->id,
                        'product_name' => $payloadProduct->name,
                        'product_slug' => $payloadProduct->slug,
                        'unit' => $payloadProduct->unit,
                        'price' => (int) $payloadProduct->price,
                        'quantity' => $quantity,
                        'subtotal' => $lineSubtotal,
                        'warranty_days' => $warrantyDays,
                        'warranty_expires_at' => null,
                    ]);

                    $payloadProduct->decrement('stock', $quantity);
                }

                $payment = $order->payments()->create([
                    'payment_code' => $this->generatePaymentCode(),
                    'method' => $paymentMethod,
                    'amount' => $totalAmount,
                    'status' => 'pending',
                    'notes' => $this->resolvePaymentNotes($paymentMethod),
                ]);

                return [$order->fresh(['items', 'address']), $payment];
            }, 3);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validasi checkout gagal.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Checkout gagal diproses. Silakan coba lagi.',
            ], 500);
        }

        if (! isset($validated['items'])) {
            $request->session()->forget('simple_cart');
        }

        $this->notifyAdminsWhenEnabled('notif_order_new', new AdminNewOrderNotification($order));

        return response()->json([
            'message' => 'Checkout berhasil dibuat.',
            'data' => [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment' => [
                    'payment_code' => $payment->payment_code,
                    'method' => $payment->method,
                    'status' => $payment->status,
                    'amount' => (int) $payment->amount,
                ],
                'customer' => [
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                ],
                'address' => [
                    'id' => $order->address?->id,
                    'label' => $order->address?->label,
                    'recipient_name' => $order->address?->recipient_name,
                    'phone' => $order->address?->phone,
                    'address_line' => $order->address?->address_line,
                    'city' => $order->address?->city,
                    'province' => $order->address?->province,
                    'postal_code' => $order->address?->postal_code,
                    'notes' => $order->address?->notes,
                ],
                'totals' => [
                    'subtotal' => (int) $order->subtotal,
                    'shipping_cost' => (int) $order->shipping_cost,
                    'discount_amount' => (int) $order->discount_amount,
                    'total_amount' => (int) $order->total_amount,
                ],
                'items' => $order->items->map(fn($orderItem) => [
                    'product_id' => $orderItem->product_id,
                    'product_name' => $orderItem->product_name,
                    'product_slug' => $orderItem->product_slug,
                    'unit' => $orderItem->unit,
                    'price' => (int) $orderItem->price,
                    'quantity' => (int) $orderItem->quantity,
                    'subtotal' => (int) $orderItem->subtotal,
                    'warranty_days' => (int) $orderItem->warranty_days,
                ])->values(),
            ],
        ], 201);
    }

    private function resolveCheckoutItems(mixed $itemsPayload, array $sessionCart): array
    {
        $normalizedItems = [];

        if (is_array($itemsPayload) && count($itemsPayload) > 0) {
            foreach ($itemsPayload as $itemPayload) {
                $productId = (int) ($itemPayload['product_id'] ?? 0);
                $quantity = max(1, (int) ($itemPayload['quantity'] ?? 1));

                if ($productId < 1) {
                    continue;
                }

                $normalizedItems[$productId] = ($normalizedItems[$productId] ?? 0) + $quantity;
            }

            return $normalizedItems;
        }

        foreach ($sessionCart as $cartKey => $cartItem) {
            $productId = (int) ($cartItem['product_id'] ?? $cartKey);
            $quantity = max(1, (int) ($cartItem['qty'] ?? 1));

            if ($productId < 1) {
                continue;
            }

            $normalizedItems[$productId] = ($normalizedItems[$productId] ?? 0) + $quantity;
        }

        return $normalizedItems;
    }

    private function resolveCheckoutAddress(User $user, array $validated): Address
    {
        $address = null;

        if (! empty($validated['address_id'])) {
            $address = $user->addresses()->whereKey((int) $validated['address_id'])->first();
        }

        if (! $address && $this->hasNewAddressInput($validated)) {
            $shouldSetDefault = (bool) ($validated['set_as_default'] ?? false) || ! $user->addresses()->exists();

            if ($shouldSetDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address = Address::create([
                'user_id' => $user->id,
                'label' => $validated['address_label'] ?? 'Alamat Baru',
                'recipient_name' => (string) $validated['recipient_name'],
                'phone' => (string) $validated['address_phone'],
                'address_line' => (string) $validated['address_line'],
                'city' => (string) $validated['city'],
                'province' => (string) $validated['province'],
                'postal_code' => (string) $validated['postal_code'],
                'notes' => $validated['address_notes'] ?? null,
                'is_default' => $shouldSetDefault,
            ]);

            return $address;
        }

        if (! $address) {
            $address = $user->addresses()->where('is_default', true)->first()
                ?? $user->addresses()->latest()->first();
        }

        if (! $address) {
            throw ValidationException::withMessages([
                'address_id' => ['Alamat tidak ditemukan. Pilih alamat atau isi alamat baru.'],
            ]);
        }

        if ((bool) ($validated['set_as_default'] ?? false) && ! $address->is_default) {
            $user->addresses()->update(['is_default' => false]);
            $address->is_default = true;
            $address->save();
        }

        return $address;
    }

    private function hasNewAddressInput(array $validated): bool
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
            if (filled($validated[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function buildOrderNotes(int $totalQuantity, int $shippingCost, array $validated, Address $address): string
    {
        $notes = [
            'Order dibuat dari API checkout.',
            'Total item: ' . $totalQuantity,
            'Ongkir total: Rp ' . number_format($shippingCost, 0, ',', '.'),
            'Alamat: ' . implode(', ', array_filter([
                $address->address_line,
                $address->city,
                $address->province,
                $address->postal_code,
            ])),
        ];

        if (! empty($validated['address_notes'])) {
            $notes[] = 'Catatan alamat: ' . $validated['address_notes'];
        }

        if (! empty($validated['notes'])) {
            $notes[] = 'Catatan customer: ' . $validated['notes'];
        }

        return implode(' | ', $notes);
    }

    private function resolvePaymentNotes(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'cod' => 'Bayar di tempat saat barang sampai (COD).',
            'bank_transfer' => 'Menunggu transfer bank dan upload bukti pembayaran. Setelah upload, status menunggu ACC admin.',
            'ewallet' => 'Menunggu pembayaran e-wallet dan upload bukti pembayaran. Setelah upload, status menunggu ACC admin.',
            default => 'Menunggu pembayaran.',
        };
    }

    private function generateOrderCode(): string
    {
        do {
            $orderCode = 'ORD-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Order::query()->where('order_code', $orderCode)->exists());

        return $orderCode;
    }

    private function generatePaymentCode(): string
    {
        do {
            $paymentCode = 'PAY-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Payment::query()->where('payment_code', $paymentCode)->exists());

        return $paymentCode;
    }

    private function shippingCostPerItem(): int
    {
        if (! Schema::hasTable('system_settings')) {
            return 5000;
        }

        $rawValue = (string) Setting::get('shipping_cost_per_item', '5000');
        $normalized = preg_replace('/[^0-9]/', '', $rawValue);

        if ($normalized === null || $normalized === '') {
            return 5000;
        }

        return max(0, (int) $normalized);
    }

    private function calculateShippingCost(int $totalQuantity): int
    {
        return max(0, $totalQuantity) * $this->shippingCostPerItem();
    }

    private function notifyAdminsWhenEnabled(string $toggleSettingKey, NotificationMessage $notification): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        if (! Setting::get($toggleSettingKey, true)) {
            return;
        }

        if (! Schema::hasTable('notifications')) {
            return;
        }

        $adminRecipients = User::query()
            ->whereHas('roles', fn($query) => $query
                ->where('guard_name', 'web')
                ->whereIn('name', ['super-admin', 'admin']))
            ->get();

        if ($adminRecipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($adminRecipients, $notification);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
