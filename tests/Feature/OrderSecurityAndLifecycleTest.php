<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderSecurityAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_upload_payment_proof_for_another_users_order(): void
    {
        Storage::fake('public');

        $orderOwner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'MCB Secure',
            'slug' => 'mcb-secure',
            'price' => 45000,
            'stock' => 8,
        ]);

        [$order, $payment] = $this->createPendingOrder($orderOwner, $product, 1, now()->subMinutes(20));

        $response = $this->actingAs($otherUser)
            ->from(route('home.tracking'))
            ->post(route('home.tracking.proof', $order->order_code), [
                'payment_proof' => UploadedFile::fake()->image('proof.png'),
            ]);

        $response->assertNotFound();

        $payment->refresh();
        $this->assertNull($payment->proof_url);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_order_owner_can_upload_payment_proof_for_own_order(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Kabel Aman',
            'slug' => 'kabel-aman',
            'price' => 120000,
            'stock' => 9,
        ]);

        [$order, $payment] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(10));

        $response = $this->actingAs($user)
            ->from(route('home.tracking'))
            ->post(route('home.tracking.proof', $order->order_code), [
                'payment_proof' => UploadedFile::fake()->image('proof-owner.jpg'),
            ]);

        $response->assertRedirect(route('home.tracking'));
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertNotNull($payment->proof_url);
        $this->assertStringStartsWith('payments/' . $order->order_code . '/', (string) $payment->proof_url);
        $this->assertTrue(Storage::disk('public')->exists((string) $payment->proof_url));
    }

    public function test_auto_cancel_command_cancels_only_expired_pending_orders_and_restores_stock(): void
    {
        $user = User::factory()->create();

        $expiredProduct = $this->createProduct([
            'name' => 'Lampu Auto Cancel',
            'slug' => 'lampu-auto-cancel',
            'price' => 50000,
            // Simulasi stok tersisa setelah checkout qty 2 dari stok awal 10.
            'stock' => 8,
        ]);

        [$expiredOrder, $expiredPayment] = $this->createPendingOrder(
            $user,
            $expiredProduct,
            2,
            now()->subHours(2),
        );

        $freshProduct = $this->createProduct([
            'name' => 'Saklar Fresh',
            'slug' => 'saklar-fresh',
            'price' => 25000,
            // Simulasi stok tersisa setelah checkout qty 1 dari stok awal 6.
            'stock' => 5,
        ]);

        [$freshOrder, $freshPayment] = $this->createPendingOrder(
            $user,
            $freshProduct,
            1,
            now()->subMinutes(20),
        );

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        $expiredOrder->refresh();
        $expiredPayment->refresh();
        $expiredProduct->refresh();

        $this->assertSame('cancelled', $expiredOrder->status);
        $this->assertSame('failed', $expiredOrder->payment_status);
        $this->assertSame('void', $expiredOrder->warranty_status);
        $this->assertStringContainsString('Auto-cancel sistem', (string) $expiredOrder->notes);
        $this->assertSame('failed', $expiredPayment->status);
        $this->assertNull($expiredPayment->paid_at);
        $this->assertSame(10, (int) $expiredProduct->stock);

        $freshOrder->refresh();
        $freshPayment->refresh();
        $freshProduct->refresh();

        $this->assertSame('pending', $freshOrder->status);
        $this->assertSame('pending', $freshOrder->payment_status);
        $this->assertSame('pending', $freshPayment->status);
        $this->assertSame(5, (int) $freshProduct->stock);
    }

    public function test_user_cannot_submit_second_active_warranty_claim_for_same_order_item(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Stop Kontak Klaim',
            'slug' => 'stop-kontak-klaim',
            'price' => 30000,
            'stock' => 4,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(30));

        $firstResponse = $this->actingAs($user)
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Produk mati total setelah dipakai beberapa hari.',
            ]);

        $firstResponse->assertRedirect(route('home.cart'));
        $firstResponse->assertSessionHas('success');
        $this->assertDatabaseCount('warranty_claims', 1);

        $secondResponse = $this->actingAs($user)
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Klaim kedua, produk masih tidak menyala sama sekali.',
            ]);

        $secondResponse->assertRedirect(route('home.cart'));
        $secondResponse->assertSessionHas('error');
        $this->assertDatabaseCount('warranty_claims', 1);
    }

    public function test_user_cannot_submit_more_than_two_warranty_claims_for_same_order_item(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'MCB Limit Klaim',
            'slug' => 'mcb-limit-klaim',
            'price' => 99000,
            'stock' => 5,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(30));

        WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $user->id,
            'reason' => 'Klaim pertama selesai diproses.',
            'status' => 'resolved',
            'requested_at' => now()->subDays(2),
            'resolved_at' => now()->subDay(),
        ]);

        WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $user->id,
            'reason' => 'Klaim kedua selesai diproses.',
            'status' => 'rejected',
            'requested_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('home.cart'))
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Percobaan klaim ketiga harus ditolak oleh sistem.',
            ]);

        $response->assertRedirect(route('home.cart'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('warranty_claims', 2);
    }

    public function test_admin_can_update_order_item_warranty_date_within_7_to_30_day_window(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Stop Kontak Admin Garansi',
            'slug' => 'stop-kontak-admin-garansi',
            'price' => 77000,
            'stock' => 6,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($customer, $product, 1, now()->subHours(2));

        $warrantyStart = ($order->completed_at ?? $order->placed_at ?? $order->created_at)->copy()->startOfDay();
        $newExpiryDate = $warrantyStart->copy()->addDays(21)->toDateString();

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.items.update-warranty', [$order, $orderItem]), [
                'warranty_expires_at' => $newExpiryDate,
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('success');

        $orderItem->refresh();
        $this->assertSame(21, (int) $orderItem->warranty_days);
        $this->assertSame($newExpiryDate, $orderItem->warranty_expires_at?->toDateString());
    }

    public function test_admin_cannot_set_order_item_warranty_date_more_than_one_month(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Kabel Admin Garansi',
            'slug' => 'kabel-admin-garansi',
            'price' => 38000,
            'stock' => 7,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($customer, $product, 1, now()->subHours(1));

        $warrantyStart = ($order->completed_at ?? $order->placed_at ?? $order->created_at)->copy()->startOfDay();
        $invalidExpiryDate = $warrantyStart->copy()->addDays(31)->toDateString();

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.items.update-warranty', [$order, $orderItem]), [
                'warranty_expires_at' => $invalidExpiryDate,
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error');

        $orderItem->refresh();
        $this->assertSame(7, (int) $orderItem->warranty_days);
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create([
            'name' => $overrides['category_name'] ?? 'Kategori ' . Str::upper(Str::random(4)),
            'slug' => $overrides['category_slug'] ?? 'kategori-' . Str::lower(Str::random(6)),
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $overrides['name'] ?? 'Produk ' . Str::upper(Str::random(4)),
            'slug' => $overrides['slug'] ?? 'produk-' . Str::lower(Str::random(6)),
            'description' => $overrides['description'] ?? 'Produk untuk pengujian fitur transaksi.',
            'price' => $overrides['price'] ?? 10000,
            'stock' => $overrides['stock'] ?? 10,
            'unit' => $overrides['unit'] ?? 'pcs',
            'is_active' => $overrides['is_active'] ?? true,
        ]);
    }

    /**
     * @return array{0: Order, 1: Payment, 2: OrderItem}
     */
    private function createPendingOrder(User $user, Product $product, int $quantity, \DateTimeInterface $placedAt): array
    {
        $subtotal = (int) $product->price * $quantity;

        $order = Order::create([
            'order_code' => 'ORD-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'address_id' => null,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '081234567890',
            'notes' => 'Order untuk testing lifecycle.',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => $subtotal,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'placed_at' => $placedAt,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => $product->unit,
            'price' => (int) $product->price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'warranty_days' => 7,
            'warranty_expires_at' => now()->addDays(7),
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'method' => 'dummy',
            'amount' => $subtotal,
            'status' => 'pending',
            'notes' => 'Payment dummy untuk testing.',
        ]);

        return [$order, $payment, $orderItem];
    }

    private function createAdminUser(): User
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
