<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);
    }

    public function getCartSummary(User $user): array
    {
        $cart = $this->getOrCreateCart($user);
        $cart->load(['items.product']);

        $items = $cart->items->map(function (CartItem $cartItem) {
            $product = $cartItem->product;
            $quantity = (int) $cartItem->quantity;
            $unitPrice = (int) ($product?->price ?? 0);
            $stock = max(0, (int) ($product?->stock ?? 0));
            $isAvailable = (bool) ($product?->is_active) && $stock > 0;

            return [
                'product_id' => (int) $cartItem->product_id,
                'product_name' => (string) ($product?->name ?? 'Produk tidak ditemukan'),
                'product_slug' => (string) ($product?->slug ?? ''),
                'unit' => (string) ($product?->unit ?? 'pcs'),
                'price' => $unitPrice,
                'quantity' => $quantity,
                'stock' => $stock,
                'is_available' => $isAvailable,
                'subtotal' => $unitPrice * $quantity,
            ];
        })->values();

        $totalQuantity = (int) $items->sum('quantity');
        $subtotalAmount = (int) $items->sum('subtotal');
        $shippingCost = $this->calculateShippingCost($totalQuantity);

        return [
            'cart_id' => $cart->id,
            'items' => $items,
            'totals' => [
                'total_quantity' => $totalQuantity,
                'subtotal' => $subtotalAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $subtotalAmount + $shippingCost,
            ],
        ];
    }

    public function addItem(User $user, int $productId, int $quantity): array
    {
        DB::transaction(function () use ($user, $productId, $quantity) {
            $cart = $this->getOrCreateCart($user);
            $lockedProduct = Product::query()->whereKey($productId)->lockForUpdate()->first();

            if (! $lockedProduct || ! $lockedProduct->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak tersedia atau sudah nonaktif.'],
                ]);
            }

            $existingCartItem = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $newQuantity = (int) ($existingCartItem?->quantity ?? 0) + $quantity;

            if ($newQuantity > 99) {
                throw ValidationException::withMessages([
                    'quantity' => ['Jumlah item dalam cart tidak boleh lebih dari 99.'],
                ]);
            }

            if ((int) $lockedProduct->stock < $newQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stok produk tidak mencukupi untuk jumlah yang diminta.'],
                ]);
            }

            if ($existingCartItem) {
                $existingCartItem->quantity = $newQuantity;
                $existingCartItem->save();

                return;
            }

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }, 3);

        return $this->getCartSummary($user);
    }

    public function updateItem(User $user, int $productId, int $quantity): array
    {
        DB::transaction(function () use ($user, $productId, $quantity) {
            $cart = $this->getOrCreateCart($user);
            $lockedProduct = Product::query()->whereKey($productId)->lockForUpdate()->first();

            if (! $lockedProduct || ! $lockedProduct->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak tersedia atau sudah nonaktif.'],
                ]);
            }

            $existingCartItem = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $existingCartItem) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di cart.'],
                ]);
            }

            if ((int) $lockedProduct->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stok produk tidak mencukupi untuk jumlah yang diminta.'],
                ]);
            }

            $existingCartItem->quantity = $quantity;
            $existingCartItem->save();
        }, 3);

        return $this->getCartSummary($user);
    }

    public function removeItem(User $user, int $productId): array
    {
        $cart = $this->getOrCreateCart($user);

        CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->delete();

        return $this->getCartSummary($user);
    }

    public function resolveCheckoutItems(User $user, ?array $itemsPayload): array
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

            if (count($normalizedItems) > 0) {
                return $normalizedItems;
            }
        }

        $cart = $this->getOrCreateCart($user);
        $cartItems = $cart->items()->get(['product_id', 'quantity']);

        foreach ($cartItems as $cartItem) {
            $normalizedItems[(int) $cartItem->product_id] = (int) $cartItem->quantity;
        }

        if (count($normalizedItems) === 0) {
            throw ValidationException::withMessages([
                'items' => ['Keranjang kosong. Tambahkan item melalui API cart atau kirim field items.'],
            ]);
        }

        return $normalizedItems;
    }

    public function removePurchasedItems(User $user, array $checkoutItems): void
    {
        if (count($checkoutItems) === 0) {
            return;
        }

        DB::transaction(function () use ($user, $checkoutItems) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                return;
            }

            foreach ($checkoutItems as $productId => $purchasedQuantity) {
                $cartItem = CartItem::query()
                    ->where('cart_id', $cart->id)
                    ->where('product_id', (int) $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $cartItem) {
                    continue;
                }

                $remainingQuantity = (int) $cartItem->quantity - max(1, (int) $purchasedQuantity);

                if ($remainingQuantity > 0) {
                    $cartItem->quantity = $remainingQuantity;
                    $cartItem->save();

                    continue;
                }

                $cartItem->delete();
            }
        }, 3);
    }

    public function hasCartItems(User $user): bool
    {
        return $user->cart()->whereHas('items')->exists();
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
}
