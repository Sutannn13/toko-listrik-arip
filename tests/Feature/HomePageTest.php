<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_products_and_categories(): void
    {
        $category = Category::create([
            'name' => 'Lampu LED',
            'slug' => 'lampu-led',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Lampu LED 12W',
            'slug' => 'lampu-led-12w',
            'description' => 'Lampu hemat energi untuk rumah.',
            'price' => 35000,
            'stock' => 20,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Lampu LED');
        $response->assertSee('Lampu LED 12W');
    }

    public function test_home_page_shows_guest_navigation(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Masuk');
        $response->assertSee('Daftar');
    }

    public function test_home_page_contains_ai_assistant_widget_shell(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('storefrontAiAssistant');
        $response->assertSee('api\\/ai\\/chat', false);
        $response->assertSee('api\\/ai\\/feedback', false);
    }

    public function test_home_page_shows_authenticated_user_navigation(): void
    {
        Role::findOrCreate('user', 'web');

        $user = User::factory()->create([
            'name' => 'Pelanggan Arip',
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Pelanggan Arip');
        $response->assertSee('Logout');
    }

    public function test_home_page_can_search_products_by_keyword_and_category(): void
    {
        $lampCategory = Category::create([
            'name' => 'Lampu',
            'slug' => 'lampu',
        ]);

        $cableCategory = Category::create([
            'name' => 'Kabel',
            'slug' => 'kabel',
        ]);

        Product::create([
            'category_id' => $lampCategory->id,
            'name' => 'Lampu LED 12W',
            'slug' => 'lampu-led-12w',
            'description' => 'Lampu hemat energi',
            'price' => 35000,
            'stock' => 20,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $cableCategory->id,
            'name' => 'Kabel NYM 2x1.5',
            'slug' => 'kabel-nym-2x1-5',
            'description' => 'Kabel instalasi rumah',
            'price' => 125000,
            'stock' => 10,
            'unit' => 'roll',
            'is_active' => true,
        ]);

        $response = $this->get(route('home', [
            'q' => 'lampu',
            'category' => $lampCategory->id,
        ]));

        $response->assertOk();
        $response->assertSee('Lampu LED 12W');
        $response->assertDontSee('Kabel NYM 2x1.5');
    }

    public function test_user_can_open_product_detail_page_using_slug(): void
    {
        $category = Category::create([
            'name' => 'Saklar',
            'slug' => 'saklar',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Saklar Ganda',
            'slug' => 'saklar-ganda',
            'description' => 'Saklar dinding dua tombol',
            'price' => 18000,
            'stock' => 14,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this->get(route('home.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Saklar Ganda');
        $response->assertSee('Tambah ke Keranjang');
    }

    public function test_buy_button_adds_product_to_simple_cart_session(): void
    {
        Role::findOrCreate('user', 'web');
        $user = User::factory()->create();
        $user->assignRole('user');

        $category = Category::create([
            'name' => 'MCB',
            'slug' => 'mcb',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'MCB 6A',
            'slug' => 'mcb-6a',
            'description' => 'Pengaman arus listrik',
            'price' => 42000,
            'stock' => 7,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('home.products.show', $product->slug))
            ->post(route('home.products.buy', $product->slug), [
                'qty' => 2,
            ]);

        $response->assertRedirect(route('home.products.show', $product->slug));
        $response->assertSessionHas('simple_cart.' . $product->id . '.qty', 2);
        $response->assertSessionHas('success');
    }

    public function test_cart_page_displays_items_from_session_simple_cart(): void
    {
        Role::findOrCreate('user', 'web');
        $user = User::factory()->create();
        $user->assignRole('user');

        $category = Category::create([
            'name' => 'Stop Kontak',
            'slug' => 'stop-kontak',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Stop Kontak 4 Lubang',
            'slug' => 'stop-kontak-4-lubang',
            'description' => 'Stop kontak untuk rumah',
            'price' => 56000,
            'stock' => 12,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'simple_cart' => [
                    $product->id => [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => (int) $product->price,
                        'unit' => $product->unit,
                        'qty' => 2,
                    ],
                ],
            ])
            ->get(route('home.cart'));

        $response->assertOk();
        $response->assertSee('Stop Kontak 4 Lubang');
        $response->assertSee('Checkout & Bayar', false);
    }

    public function test_user_can_update_cart_quantity(): void
    {
        Role::findOrCreate('user', 'web');
        $user = User::factory()->create();
        $user->assignRole('user');

        $category = Category::create([
            'name' => 'Fitting',
            'slug' => 'fitting',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Fitting E27',
            'slug' => 'fitting-e27',
            'description' => 'Fitting lampu standar',
            'price' => 8000,
            'stock' => 25,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'simple_cart' => [
                    $product->id => [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => (int) $product->price,
                        'unit' => $product->unit,
                        'qty' => 1,
                    ],
                ],
            ])
            ->patch(route('home.cart.update', $product->id), [
                'qty' => 5,
            ]);

        $response->assertRedirect(route('home.cart'));
        $response->assertSessionHas('simple_cart.' . $product->id . '.qty', 5);
    }

    public function test_checkout_persists_order_and_clears_simple_cart(): void
    {
        Role::findOrCreate('user', 'web');
        $user = User::factory()->create([
            'name' => 'Pelanggan Checkout',
            'email' => 'checkout-user@example.com',
        ]);
        $user->assignRole('user');

        $category = Category::create([
            'name' => 'Saklar',
            'slug' => 'saklar',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Saklar Tunggal',
            'slug' => 'saklar-tunggal',
            'description' => 'Saklar satu tombol',
            'price' => 12000,
            'stock' => 30,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
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
            ->post(route('home.cart.checkout'), [
                'customer_name' => 'Pelanggan Checkout',
                'customer_email' => 'checkout-user@example.com',
                'customer_phone' => '081234567890',
                'address_label' => 'Rumah',
                'recipient_name' => 'Pelanggan Checkout',
                'address_phone' => '081234567890',
                'address_line' => 'Jl. Mawar No. 12',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40123',
                'address_notes' => 'Pagar hitam',
                'set_as_default' => '1',
            ]);

        $response->assertRedirect(route('home.cart'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('simple_cart');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payments', 1);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $response->assertSessionHas('checkout_order_code', $order?->order_code);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_name' => 'Pelanggan Checkout',
            'customer_email' => 'checkout-user@example.com',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 36000,
            'shipping_cost' => 15000,
            'total_amount' => 51000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_name' => 'Saklar Tunggal',
            'quantity' => 3,
            'subtotal' => 36000,
        ]);

        $this->assertDatabaseHas('payments', [
            'method' => 'dummy',
            'amount' => 51000,
            'status' => 'pending',
        ]);

        $product->refresh();
        $this->assertSame(27, (int) $product->stock);
    }

    public function test_checkout_can_use_selected_saved_address_without_reentering_new_address_fields(): void
    {
        Role::findOrCreate('user', 'web');

        $user = User::factory()->create([
            'name' => 'User Default Alamat',
            'email' => 'default-address@example.com',
        ]);
        $user->assignRole('user');

        $defaultAddress = Address::create([
            'user_id' => $user->id,
            'label' => 'Rumah Utama',
            'recipient_name' => 'User Default Alamat',
            'phone' => '081111111111',
            'address_line' => 'Jl. Cendana No. 88',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40123',
            'is_default' => true,
        ]);

        $category = Category::create([
            'name' => 'Kabel',
            'slug' => 'kabel',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kabel NYA 1.5',
            'slug' => 'kabel-nya-1-5',
            'description' => 'Kabel listrik untuk instalasi rumah.',
            'price' => 18000,
            'stock' => 20,
            'unit' => 'roll',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'simple_cart' => [
                    $product->id => [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => (int) $product->price,
                        'unit' => $product->unit,
                        'qty' => 2,
                    ],
                ],
            ])
            ->post(route('home.cart.checkout'), [
                'address_id' => $defaultAddress->id,
                'payment_method' => 'cod',
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => '081111111111',
            ]);

        $response->assertRedirect(route('home.cart'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'address_id' => $defaultAddress->id,
            'customer_name' => 'User Default Alamat',
            'customer_email' => 'default-address@example.com',
            'subtotal' => 36000,
            'shipping_cost' => 10000,
            'total_amount' => 46000,
        ]);
    }

    public function test_tracking_page_lists_users_orders_without_order_code_input(): void
    {
        Role::findOrCreate('user', 'web');

        $user = User::factory()->create([
            'name' => 'User Tracking',
            'email' => 'tracking-user@example.com',
        ]);
        $user->assignRole('user');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        $category = Category::create([
            'name' => 'Tracking',
            'slug' => 'tracking',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Lampu Tracking',
            'slug' => 'lampu-tracking',
            'description' => 'Produk untuk test tracking otomatis',
            'price' => 22000,
            'stock' => 100,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $myOrder = Order::create([
            'order_code' => 'ORD-ARIP-20260412-MY0001',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '081234567890',
            'status' => 'processing',
            'payment_status' => 'paid',
            'warranty_status' => 'active',
            'subtotal' => 22000,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => 22000,
            'placed_at' => now(),
        ]);

        $myOrder->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => 'pcs',
            'price' => 22000,
            'quantity' => 1,
            'subtotal' => 22000,
            'warranty_days' => 7,
            'warranty_expires_at' => now()->addDays(7),
        ]);

        $myOrder->payments()->create([
            'payment_code' => 'PAY-ARIP-20260412-MY0001',
            'method' => 'bank_transfer',
            'amount' => 22000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $otherOrder = Order::create([
            'order_code' => 'ORD-ARIP-20260412-OT0001',
            'user_id' => $otherUser->id,
            'customer_name' => $otherUser->name,
            'customer_email' => $otherUser->email,
            'customer_phone' => '081234567891',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => 10000,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => 10000,
            'placed_at' => now(),
        ]);

        $otherOrder->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => 'pcs',
            'price' => 10000,
            'quantity' => 1,
            'subtotal' => 10000,
            'warranty_days' => 7,
            'warranty_expires_at' => now()->addDays(7),
        ]);

        $otherOrder->payments()->create([
            'payment_code' => 'PAY-ARIP-20260412-OT0001',
            'method' => 'dummy',
            'amount' => 10000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking'));

        $response->assertOk();
        $response->assertSee('ORD-ARIP-20260412-MY0001');
        $response->assertDontSee('ORD-ARIP-20260412-OT0001');
        $response->assertSee(route('home.tracking.show', 'ORD-ARIP-20260412-MY0001'));
    }
}
