<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiMobileFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_flow_can_login_get_me_add_cart_and_checkout_with_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile-flow-user@example.com',
            'password' => 'mobile-flow-pass',
        ]);

        $category = Category::create([
            'name' => 'Mobile Flow Category',
            'slug' => 'mobile-flow-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Stop Kontak 4 Lubang',
            'slug' => 'stop-kontak-4-lubang',
            'description' => 'Produk untuk test full flow mobile API.',
            'price' => 25000,
            'stock' => 12,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);

        $loginResponse = $this->postJson(route('api.auth.token.store'), [
            'email' => 'mobile-flow-user@example.com',
            'password' => 'mobile-flow-pass',
            'device_name' => 'android-mobile-flow',
        ]);

        $loginResponse->assertCreated();
        $accessToken = (string) $loginResponse->json('data.access_token');

        $meResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->getJson(route('api.auth.me'));

        $meResponse->assertOk();
        $meResponse->assertJsonPath('message', 'Profil user berhasil diambil.');
        $meResponse->assertJsonPath('data.id', $user->id);

        $addCartResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson(route('api.cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $addCartResponse->assertCreated();
        $addCartResponse->assertJsonPath('data.totals.total_quantity', 2);

        $checkoutResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson(route('api.checkout.store'), [
                'payment_method' => 'bank_transfer',
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => '081230001234',
                'address_label' => 'Rumah',
                'recipient_name' => $user->name,
                'address_phone' => '081230001234',
                'address_line' => 'Jl. Merdeka No. 100',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40111',
            ]);

        $checkoutResponse->assertCreated();
        $checkoutResponse->assertJsonPath('message', 'Checkout berhasil dibuat.');
        $checkoutResponse->assertJsonPath('data.payment.method', 'bank_transfer');
        $checkoutResponse->assertJsonPath('data.totals.subtotal', 50000);

        $createdOrder = Order::query()->where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($createdOrder);

        $this->assertDatabaseHas('payments', [
            'order_id' => $createdOrder->id,
            'method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $product->id,
        ]);
    }
}
