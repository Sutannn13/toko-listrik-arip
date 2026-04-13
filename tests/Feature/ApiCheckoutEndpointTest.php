<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCheckoutEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_checkout_creates_order_and_payment_from_payload_items(): void
    {
        [$customer, $product] = $this->createCustomerAndProduct();

        $response = $this->actingAs($customer)
            ->postJson(route('api.checkout.store'), [
                'payment_method' => 'bank_transfer',
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '081200001111',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
                'address_label' => 'Rumah',
                'recipient_name' => $customer->name,
                'address_phone' => '081200001111',
                'address_line' => 'Jl. Cihampelas No. 10',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40112',
                'address_notes' => 'Patokan dekat minimarket.',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Checkout berhasil dibuat.');
        $response->assertJsonPath('data.payment.method', 'bank_transfer');
        $response->assertJsonPath('data.totals.subtotal', 30000);
        $response->assertJsonPath('data.totals.shipping_cost', 10000);
        $response->assertJsonPath('data.totals.total_amount', 40000);
        $response->assertJsonCount(1, 'data.items');

        $createdOrder = Order::query()->where('user_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($createdOrder);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $createdOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 30000,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $createdOrder->id,
            'method' => 'bank_transfer',
            'amount' => 40000,
            'status' => 'pending',
        ]);

        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_api_checkout_can_use_simple_cart_session_when_items_payload_is_missing(): void
    {
        [$customer, $product] = $this->createCustomerAndProduct();

        $response = $this->actingAs($customer)
            ->withSession([
                'simple_cart' => [
                    $product->id => [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => (int) $product->price,
                        'unit' => $product->unit,
                        'qty' => 3,
                    ],
                ],
            ])
            ->postJson(route('api.checkout.store'), [
                'payment_method' => 'cod',
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '081200002222',
                'address_label' => 'Kantor',
                'recipient_name' => $customer->name,
                'address_phone' => '081200002222',
                'address_line' => 'Jl. Asia Afrika No. 99',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40115',
            ]);

        $response->assertCreated();
        $response->assertSessionMissing('simple_cart');
        $response->assertJsonPath('data.payment.method', 'cod');
        $response->assertJsonPath('data.totals.subtotal', 45000);
        $response->assertJsonPath('data.totals.shipping_cost', 15000);
        $response->assertJsonPath('data.totals.total_amount', 60000);
    }

    public function test_api_checkout_returns_validation_error_when_items_and_session_cart_are_empty(): void
    {
        [$customer] = $this->createCustomerAndProduct();

        $response = $this->actingAs($customer)
            ->postJson(route('api.checkout.store'), [
                'payment_method' => 'ewallet',
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '081200003333',
                'address_label' => 'Rumah',
                'recipient_name' => $customer->name,
                'address_phone' => '081200003333',
                'address_line' => 'Jl. Braga No. 7',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40111',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['items']);
    }

    private function createCustomerAndProduct(): array
    {
        $customer = User::factory()->create([
            'email' => 'checkout-api-user@example.com',
        ]);

        $category = Category::create([
            'name' => 'API Checkout Category',
            'slug' => 'api-checkout-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'MCB 10A',
            'slug' => 'mcb-10a',
            'description' => 'Produk untuk test endpoint API checkout.',
            'price' => 15000,
            'stock' => 10,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);

        return [$customer, $product];
    }
}
