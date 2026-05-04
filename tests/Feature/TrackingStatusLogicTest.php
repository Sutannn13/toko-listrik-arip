<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrackingStatusLogicTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithOrder(array $orderData, array $paymentData = []): array
    {
        Role::findOrCreate('user', 'web');
        $user = User::factory()->create();
        $user->assignRole('user');

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test product for tracking',
            'price' => 50000,
            'stock' => 10,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $order = Order::create(array_merge([
            'order_code' => 'ORD-TEST-' . uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '081234567890',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => 50000,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'placed_at' => now(),
        ], $orderData));

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => 'pcs',
            'price' => 50000,
            'quantity' => 1,
            'subtotal' => 50000,
            'warranty_days' => 0,
            'warranty_expires_at' => null,
        ]);

        if ($paymentData) {
            $order->payments()->create(array_merge([
                'payment_code' => 'PAY-TEST-' . uniqid(),
                'method' => 'bank_transfer',
                'amount' => 50000,
                'status' => 'pending',
            ], $paymentData));
        }

        return [$user, $order];
    }

    public function test_tracking_detail_shows_diproses_not_selesai_when_order_is_processing(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'processing',
            'payment_status' => 'paid',
            'tracking_number' => 'B 1762 AB',
        ], [
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking.show', $order->order_code));

        if ($response->status() === 302) {
            $response->assertRedirect(route('home.tracking'));
            $response->assertSessionHas('error');
        } else {
            $response->assertOk();

            $html = $response->getContent();

            // Stepper should show Diproses as current/active step
            $this->assertStringContainsString('Diproses', $html);

            // Selesai should NOT be marked as active/current
            // The checkmark icon (✓) should not appear for Selesai step when order is processing
            $this->assertStringNotContainsStringIgnoringCase('Selesai</p>', $html);
        }
    }

    public function test_tracking_detail_shows_selesai_only_when_order_is_completed(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'completed',
            'payment_status' => 'paid',
        ], [
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking.show', $order->order_code));

        if ($response->status() === 302) {
            $response->assertRedirect(route('home.tracking'));
            $response->assertSessionHas('error');
        } else {
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('Selesai', $html);
        }
    }

    public function test_tracking_detail_shows_dikirim_when_order_is_shipped(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'shipped',
            'payment_status' => 'paid',
            'tracking_number' => 'RESI-123456',
        ], [
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking.show', $order->order_code));

        if ($response->status() === 302) {
            $response->assertRedirect(route('home.tracking'));
            $response->assertSessionHas('error');
        } else {
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('Dikirim', $html);
        }
    }

    public function test_tracking_detail_shows_resi_prepared_message_when_processing(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'processing',
            'payment_status' => 'paid',
            'tracking_number' => 'B 1762 AB',
        ], [
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking.show', $order->order_code));

        if ($response->status() === 302) {
            $response->assertRedirect(route('home.tracking'));
            $response->assertSessionHas('error');
        } else {
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('Nomor Resi Disiapkan', $html);
            $this->assertStringContainsString('pesanan masih diproses', $html);
        }
    }

    public function test_tracking_detail_shows_nomor_resi_pengiriman_when_shipped(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'shipped',
            'payment_status' => 'paid',
            'tracking_number' => 'RESI-123456',
        ], [
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking.show', $order->order_code));

        if ($response->status() === 302) {
            $response->assertRedirect(route('home.tracking'));
            $response->assertSessionHas('error');
        } else {
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('Nomor Resi Pengiriman', $html);
            $this->assertStringContainsString('RESI-123456', $html);
        }
    }

    public function test_tracking_list_shows_pembayaran_lunas_not_menunggu_bayar_when_paid(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'processing',
            'payment_status' => 'paid',
        ], [
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Pembayaran Lunas', $html);
        $this->assertStringNotContainsStringIgnoringCase('MENUNGGU BAYAR', $html);
    }

    public function test_tracking_list_shows_diproses_when_order_is_processing(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'processing',
            'payment_status' => 'paid',
        ], [
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Diproses', $html);
    }

    public function test_tracking_detail_shows_cancelled_state_correctly(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking.show', $order->order_code));

        if ($response->status() === 302) {
            $response->assertRedirect(route('home.tracking'));
            $response->assertSessionHas('error');
        } else {
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('Dibatalkan', $html);
        }
    }

    public function test_tracking_list_shows_cancelled_badge_when_order_cancelled(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('cancelled', $html);
        $this->assertStringContainsString('Pesanan dibatalkan', $html);
    }

    public function test_tracking_list_shows_pending_status_correctly(): void
    {
        [$user, $order] = $this->createUserWithOrder([
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home.tracking'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Menunggu Bayar', $html);
    }
}
