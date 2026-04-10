<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyClaim;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_provides_live_overview_triage_and_metrics_data(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('user');

        $category = Category::create([
            'name' => 'Dashboard Test',
            'slug' => 'dashboard-test',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kipas Dashboard',
            'slug' => 'kipas-dashboard',
            'description' => 'Produk untuk test metrik dashboard admin.',
            'price' => 100000,
            'stock' => 20,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);

        $dayThree = now()->subDays(3)->startOfDay()->addHours(9);
        $dayOne = now()->subDay()->startOfDay()->addHours(10);
        $today = now()->startOfDay()->addHours(11);

        [$pendingOrder, $pendingOrderItem] = $this->createOrderWithPayment(
            user: $customer,
            product: $product,
            status: 'pending',
            paymentStatus: 'pending',
            totalAmount: 100000,
            proofUrl: 'payments/ORD-TEST-PENDING/proof.jpg',
            createdAt: $dayThree,
        );

        $this->createOrderWithPayment(
            user: $customer,
            product: $product,
            status: 'processing',
            paymentStatus: 'paid',
            totalAmount: 200000,
            createdAt: $dayOne,
            paidAt: $dayOne,
        );

        [$completedOrder] = $this->createOrderWithPayment(
            user: $customer,
            product: $product,
            status: 'completed',
            paymentStatus: 'paid',
            totalAmount: 150000,
            createdAt: $today,
            paidAt: $today,
        );

        WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-OLD001',
            'order_id' => $pendingOrder->id,
            'order_item_id' => $pendingOrderItem->id,
            'user_id' => $customer->id,
            'reason' => 'Klaim lama untuk SLA overdue.',
            'status' => 'submitted',
            'requested_at' => $dayThree,
            'created_at' => $dayThree,
            'updated_at' => $dayThree,
        ]);

        WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-NEW001',
            'order_id' => $pendingOrder->id,
            'order_item_id' => $pendingOrderItem->id,
            'user_id' => $customer->id,
            'reason' => 'Klaim baru masih dalam SLA.',
            'status' => 'submitted',
            'requested_at' => $today,
            'created_at' => $today,
            'updated_at' => $today,
        ]);

        $admin->notify(new OrderCompletedNotification($completedOrder));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('overview', function (array $overview): bool {
            return (int) ($overview['total_products'] ?? 0) === 1
                && (int) ($overview['active_orders'] ?? 0) === 2
                && (int) ($overview['total_customers'] ?? 0) === 1
                && (int) ($overview['unread_notifications'] ?? 0) === 1;
        });

        $response->assertViewHas('triage', function (array $triage): bool {
            return (int) ($triage['payments_to_verify'] ?? 0) === 1
                && (int) ($triage['orders_ready_to_ship'] ?? 0) === 1
                && (int) ($triage['claims_submitted'] ?? 0) === 2
                && (int) ($triage['claims_sla_overdue'] ?? 0) === 1;
        });

        $response->assertViewHas('metrics', function (array $metrics): bool {
            return (int) ($metrics['paid_revenue_30d'] ?? 0) === 350000
                && (int) ($metrics['avg_paid_order_value_30d'] ?? 0) === 175000
                && abs((float) ($metrics['fulfillment_rate_30d'] ?? 0) - 33.3) < 0.001
                && (int) ($metrics['open_claims_48h'] ?? 0) === 1
                && (int) ($metrics['pending_payment_orders'] ?? 0) === 1
                && (int) ($metrics['failed_payment_orders'] ?? 0) === 0;
        });

        $response->assertViewHas('trend7d', function (array $trend): bool {
            $revenueTrend = collect($trend['revenue'] ?? []);
            $claimsTrend = collect($trend['claims'] ?? []);
            $backlogTrend = collect($trend['backlog'] ?? []);

            return $revenueTrend->count() === 7
                && $claimsTrend->count() === 7
                && $backlogTrend->count() === 7
                && (int) $revenueTrend->sum('value') === 350000
                && (int) $claimsTrend->sum('value') === 2
                && (int) $backlogTrend->sum('value') === 4
                && $revenueTrend->every(fn($point) => isset($point['label'], $point['short_label'], $point['value'], $point['height']));
        });

        $response->assertSee('proof=uploaded');
        $response->assertSee('age_bucket=sla_overdue');
        $response->assertSee('Trend 7 Hari (Mini Chart)');
    }

    private function createOrderWithPayment(
        User $user,
        Product $product,
        string $status,
        string $paymentStatus,
        int $totalAmount,
        ?string $proofUrl = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $paidAt = null,
    ): array {
        $createdAt = \Illuminate\Support\Carbon::instance($createdAt ?? now()->subDays(2));

        $order = Order::create([
            'order_code' => 'ORD-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '081234567890',
            'status' => $status,
            'payment_status' => $paymentStatus,
            'warranty_status' => 'active',
            'subtotal' => $totalAmount,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'placed_at' => $createdAt,
            'paid_at' => $paymentStatus === 'paid' ? ($paidAt ?? $createdAt) : null,
            'completed_at' => $status === 'completed' ? ($paidAt ?? $createdAt) : null,
        ]);

        $order->timestamps = false;
        $order->created_at = $createdAt;
        $order->updated_at = $createdAt;
        $order->save();

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => $product->unit,
            'price' => $totalAmount,
            'quantity' => 1,
            'subtotal' => $totalAmount,
            'warranty_days' => 7,
            'warranty_expires_at' => $createdAt->copy()->addDays(7),
        ]);

        $orderItem->timestamps = false;
        $orderItem->created_at = $createdAt;
        $orderItem->updated_at = $createdAt;
        $orderItem->save();

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'method' => 'dummy',
            'amount' => $totalAmount,
            'status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? ($paidAt ?? $createdAt) : null,
            'proof_url' => $proofUrl,
            'notes' => 'Generated from admin dashboard metrics test.',
        ]);

        $payment->timestamps = false;
        $payment->created_at = $createdAt;
        $payment->updated_at = $createdAt;
        $payment->save();

        return [$order, $orderItem];
    }
}
