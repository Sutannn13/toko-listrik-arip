<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderLatestProofFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_proof_filter_uses_latest_payment_only(): void
    {
        $admin = $this->createAdmin();
        $customer = User::factory()->create();

        $product = $this->createProduct();

        $orderOldProofOnly = $this->createOrderWithProofHistory($customer, $product, [
            'payments/old-proof.jpg',
            null,
        ]);

        $orderLatestProof = $this->createOrderWithProofHistory($customer, $product, [
            null,
            'payments/latest-proof.jpg',
        ]);

        $orderMissingProof = $this->createOrderWithProofHistory($customer, $product, [
            null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index', [
            'payment_status' => 'pending',
            'proof' => 'uploaded',
        ]));

        $response->assertOk();
        $response->assertSee($orderLatestProof->order_code);
        $response->assertDontSee($orderOldProofOnly->order_code);
        $response->assertDontSee($orderMissingProof->order_code);
    }

    public function test_missing_proof_filter_uses_latest_payment_only(): void
    {
        $admin = $this->createAdmin();
        $customer = User::factory()->create();

        $product = $this->createProduct();

        $orderOldProofOnly = $this->createOrderWithProofHistory($customer, $product, [
            'payments/old-proof.jpg',
            null,
        ]);

        $orderLatestProof = $this->createOrderWithProofHistory($customer, $product, [
            null,
            'payments/latest-proof.jpg',
        ]);

        $orderMissingProof = $this->createOrderWithProofHistory($customer, $product, [
            null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index', [
            'payment_status' => 'pending',
            'proof' => 'missing',
        ]));

        $response->assertOk();
        $response->assertSee($orderOldProofOnly->order_code);
        $response->assertSee($orderMissingProof->order_code);
        $response->assertDontSee($orderLatestProof->order_code);
    }

    private function createAdmin(): User
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function createProduct(): Product
    {
        $category = Category::create([
            'name' => 'Filter Proof',
            'slug' => 'filter-proof',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'MCB Filter Proof',
            'slug' => 'mcb-filter-proof',
            'description' => 'Produk untuk test filter proof payment terbaru.',
            'price' => 99000,
            'stock' => 50,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);
    }

    /**
     * @param array<int, string|null> $proofHistory
     */
    private function createOrderWithProofHistory(User $customer, Product $product, array $proofHistory): Order
    {
        $totalAmount = 99000;

        $order = Order::create([
            'order_code' => 'ORD-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => $totalAmount,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => $product->unit,
            'price' => $totalAmount,
            'quantity' => 1,
            'subtotal' => $totalAmount,
            'warranty_days' => 7,
            'warranty_expires_at' => now()->addDays(7),
        ]);

        foreach ($proofHistory as $proofUrl) {
            Payment::create([
                'order_id' => $order->id,
                'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'method' => 'dummy',
                'amount' => $totalAmount,
                'status' => 'pending',
                'proof_url' => $proofUrl,
                'notes' => 'Generated from latest proof filter test.',
            ]);
        }

        return $order;
    }
}
