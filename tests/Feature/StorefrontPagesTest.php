<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_history_page_only_shows_authenticated_users_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Panel Lampu Utama',
            'slug' => 'panel-lampu-utama',
            'price' => 120000,
            'is_electronic' => true,
        ]);

        $userOrder = $this->createOrderWithSingleItem($user, $product, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $otherOrder = $this->createOrderWithSingleItem($otherUser, $product, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get(route('home.transactions'));

        $response->assertOk();
        $response->assertSee($userOrder->order_code);
        $response->assertDontSee($otherOrder->order_code);
    }

    public function test_notifications_page_and_mark_all_read_flow_work_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Kipas Notifikasi',
            'slug' => 'kipas-notifikasi',
            'price' => 175000,
            'is_electronic' => true,
        ]);

        $userOrder = $this->createOrderWithSingleItem($user, $product, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $otherOrder = $this->createOrderWithSingleItem($otherUser, $product, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $user->notify(new OrderCompletedNotification($userOrder));
        $otherUser->notify(new OrderCompletedNotification($otherOrder));

        $indexResponse = $this->actingAs($user)->get(route('home.notifications.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee($userOrder->order_code);
        $indexResponse->assertDontSee($otherOrder->order_code);

        $this->assertSame(1, $user->unreadNotifications()->count());

        $markReadResponse = $this->actingAs($user)->post(route('home.notifications.read-all'));

        $markReadResponse->assertRedirect(route('home.notifications.index'));
        $markReadResponse->assertSessionHas('success');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_storefront_header_shows_notification_preview_for_user(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Socket Preview',
            'slug' => 'socket-preview',
            'price' => 95000,
            'is_electronic' => true,
        ]);

        $order = $this->createOrderWithSingleItem($user, $product, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $user->notify(new OrderCompletedNotification($order));

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Notifikasi');
        $response->assertSee($order->order_code);
        $response->assertSee('Lihat Semua Notifikasi');
    }

    public function test_custom_landing_header_shows_notification_preview_for_user(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Kabel Landing Preview',
            'slug' => 'kabel-landing-preview',
            'price' => 120000,
            'is_electronic' => true,
        ]);

        $order = $this->createOrderWithSingleItem($user, $product, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $user->notify(new OrderCompletedNotification($order));

        $response = $this->actingAs($user)->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Notifikasi');
        $response->assertSee($order->order_code);
        $response->assertSee('Lihat Semua Notifikasi');
    }

    public function test_opening_user_notification_preview_marks_it_as_read(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Panel Open Preview',
            'slug' => 'panel-open-preview',
            'price' => 135000,
            'is_electronic' => true,
        ]);

        $order = $this->createOrderWithSingleItem($user, $product, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $user->notify(new OrderCompletedNotification($order));

        $notification = $user->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $response = $this->actingAs($user)
            ->get(route('home.notifications.open', ['notification' => $notification->id]));

        $response->assertRedirect(route('home.transactions', absolute: false));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_warranty_center_page_lists_only_users_eligible_items_and_respects_status_filter(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $electronic = $this->createProduct([
            'name' => 'Blender Premium',
            'slug' => 'blender-premium',
            'price' => 350000,
            'is_electronic' => true,
        ]);

        $nonElectronic = $this->createProduct([
            'name' => 'Pipa Instalasi',
            'slug' => 'pipa-instalasi',
            'price' => 45000,
            'is_electronic' => false,
        ]);

        $activeOrder = $this->createOrderWithSingleItem($user, $electronic, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $expiredOrder = $this->createOrderWithSingleItem($user, $electronic, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $otherUsersOrder = $this->createOrderWithSingleItem($otherUser, $electronic, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $nonElectronicOrder = $this->createOrderWithSingleItem($user, $nonElectronic, [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $activeOrder->items()->update([
            'warranty_days' => 7,
            'warranty_expires_at' => now()->addDays(4),
            'product_name' => 'Blender Premium Aktif',
        ]);

        $expiredOrder->items()->update([
            'warranty_days' => 7,
            'warranty_expires_at' => now()->subDays(1),
            'product_name' => 'Blender Premium Expired',
        ]);

        $otherUsersOrder->items()->update([
            'warranty_days' => 7,
            'warranty_expires_at' => now()->addDays(3),
            'product_name' => 'Blender User Lain',
        ]);

        $nonElectronicOrder->items()->update([
            'warranty_days' => 0,
            'warranty_expires_at' => null,
            'product_name' => 'Pipa Non Garansi',
        ]);

        $allResponse = $this->actingAs($user)->get(route('home.warranty'));

        $allResponse->assertOk();
        $allResponse->assertSee('Blender Premium Aktif');
        $allResponse->assertSee('Blender Premium Expired');
        $allResponse->assertDontSee('Blender User Lain');
        $allResponse->assertDontSee('Pipa Non Garansi');

        $activeResponse = $this->actingAs($user)->get(route('home.warranty', ['status' => 'active']));

        $activeResponse->assertOk();
        $activeResponse->assertSee('Blender Premium Aktif');
        $activeResponse->assertDontSee('Blender Premium Expired');
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create([
            'name' => 'Kategori ' . Str::upper(Str::random(5)),
            'slug' => 'kategori-' . Str::lower(Str::random(6)),
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $overrides['name'] ?? 'Produk Test ' . Str::upper(Str::random(4)),
            'slug' => $overrides['slug'] ?? 'produk-test-' . Str::lower(Str::random(6)),
            'description' => 'Produk test untuk halaman storefront.',
            'price' => $overrides['price'] ?? 10000,
            'stock' => $overrides['stock'] ?? 10,
            'unit' => $overrides['unit'] ?? 'pcs',
            'is_active' => true,
            'is_electronic' => $overrides['is_electronic'] ?? true,
        ]);
    }

    private function createOrderWithSingleItem(User $user, Product $product, array $orderOverrides = []): Order
    {
        $quantity = (int) ($orderOverrides['quantity'] ?? 1);
        $subtotal = (int) $product->price * $quantity;
        $warrantyDays = $product->is_electronic ? 7 : 0;

        $order = Order::create([
            'order_code' => $orderOverrides['order_code'] ?? 'ORD-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'customer_name' => $orderOverrides['customer_name'] ?? $user->name,
            'customer_email' => $orderOverrides['customer_email'] ?? $user->email,
            'customer_phone' => $orderOverrides['customer_phone'] ?? '081234567890',
            'notes' => 'Order test storefront page.',
            'status' => $orderOverrides['status'] ?? 'pending',
            'payment_status' => $orderOverrides['payment_status'] ?? 'pending',
            'warranty_status' => $orderOverrides['warranty_status'] ?? 'active',
            'subtotal' => $subtotal,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'placed_at' => now(),
            'paid_at' => ($orderOverrides['payment_status'] ?? 'pending') === 'paid' ? now() : null,
            'completed_at' => ($orderOverrides['status'] ?? 'pending') === 'completed' ? now() : null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => $product->unit,
            'price' => (int) $product->price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'warranty_days' => $warrantyDays,
            'warranty_expires_at' => $warrantyDays > 0 ? now()->addDays($warrantyDays) : null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'method' => 'dummy',
            'amount' => $subtotal,
            'status' => $order->payment_status,
            'paid_at' => $order->payment_status === 'paid' ? now() : null,
        ]);

        return $order;
    }
}
