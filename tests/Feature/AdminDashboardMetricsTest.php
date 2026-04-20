<?php

namespace Tests\Feature;

use App\Models\AiAssistantFeedback;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyClaim;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

        AiAssistantFeedback::create([
            'session_id' => 'sess-dashboard-ai-001',
            'message_id' => 'msg-dashboard-ai-001',
            'intent' => 'product_recommendation',
            'rating' => 1,
            'reason_code' => 'helpful_recommendation_accuracy',
            'reason' => 'Rekomendasi pas budget.',
            'provider' => 'gemini',
            'metadata' => [
                'provider' => 'gemini',
                'status' => 'primary_success',
            ],
            'created_at' => $today,
            'updated_at' => $today,
        ]);

        AiAssistantFeedback::create([
            'session_id' => 'sess-dashboard-ai-002',
            'message_id' => 'msg-dashboard-ai-002',
            'intent' => 'faq',
            'rating' => -1,
            'reason_code' => 'not-helpful-payment_instruction',
            'reason' => 'Jawaban kurang tepat.',
            'provider' => 'deepseek',
            'metadata' => [
                'provider' => 'deepseek',
                'status' => 'fallback_failed',
            ],
            'created_at' => $dayOne,
            'updated_at' => $dayOne,
        ]);

        AiAssistantFeedback::create([
            'session_id' => 'sess-dashboard-ai-003',
            'message_id' => 'msg-dashboard-ai-003',
            'intent' => 'faq',
            'rating' => 1,
            'reason_code' => 'helpful_generic',
            'reason' => 'Lebih jelas.',
            'provider' => 'gemini',
            'metadata' => [
                'provider' => 'gemini',
                'status' => 'fallback_success',
            ],
            'created_at' => $dayThree,
            'updated_at' => $dayThree,
        ]);

        File::ensureDirectoryExists(storage_path('app/ai-benchmarks'));
        foreach (File::files(storage_path('app/ai-benchmarks')) as $benchmarkFile) {
            if (str_starts_with(strtolower($benchmarkFile->getFilename()), 'ai-benchmark-')) {
                File::delete($benchmarkFile->getPathname());
            }
        }

        $this->writeBenchmarkReport(
            generatedAt: $dayOne->copy()->addHours(2),
            status: 'fail',
            passRatePercent: 70.0,
            thresholdPercent: 85.0,
        );

        $this->writeBenchmarkReport(
            generatedAt: $today->copy()->addHours(2),
            status: 'pass',
            passRatePercent: 92.0,
            thresholdPercent: 85.0,
        );

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

        $response->assertViewHas('aiFeedbackSummary', function (array $summary): bool {
            return (int) ($summary['total_feedback_7d'] ?? 0) === 3
                && (int) ($summary['helpful_feedback_7d'] ?? 0) === 2
                && (int) ($summary['not_helpful_feedback_7d'] ?? 0) === 1
                && abs((float) ($summary['helpful_rate_7d'] ?? 0) - 66.7) < 0.001;
        });

        $response->assertViewHas('aiFeedbackByIntent', function (array $rows): bool {
            $indexedRows = collect($rows)->keyBy('label');

            return (int) data_get($indexedRows, 'faq.total', 0) === 2
                && (int) data_get($indexedRows, 'faq.not_helpful', 0) === 1
                && (int) data_get($indexedRows, 'product_recommendation.total', 0) === 1;
        });

        $response->assertViewHas('aiFeedbackByProvider', function (array $rows): bool {
            $indexedRows = collect($rows)->keyBy('label');

            return (int) data_get($indexedRows, 'gemini.total', 0) === 2
                && (int) data_get($indexedRows, 'deepseek.total', 0) === 1;
        });

        $response->assertViewHas('aiFeedbackByReasonCode', function (array $rows): bool {
            $indexedRows = collect($rows)->keyBy('label');

            return (int) data_get($indexedRows, 'helpful_recommendation_accuracy.total', 0) === 1
                && (int) data_get($indexedRows, 'not_helpful_payment_instruction.not_helpful', 0) === 1
                && (int) data_get($indexedRows, 'helpful_generic.helpful', 0) === 1
                && (string) data_get($indexedRows, 'helpful_recommendation_accuracy.display_label', '') === 'Rekomendasi produk tepat'
                && (string) data_get($indexedRows, 'not_helpful_payment_instruction.display_label', '') === 'Instruksi pembayaran kurang jelas';
        });

        $response->assertViewHas('aiIntentRootCausePriorities', function (array $rows): bool {
            $indexedRows = collect($rows)->keyBy('intent');

            return (int) data_get($indexedRows, 'faq.priority_rank', 0) === 1
                && (string) data_get($indexedRows, 'faq.reason_code', '') === 'not_helpful_payment_instruction'
                && (int) data_get($indexedRows, 'faq.current_negative_count', 0) === 1
                && (string) data_get($indexedRows, 'faq.severity_level', '') === 'medium'
                && str_contains((string) data_get($indexedRows, 'faq.recommended_patch', ''), 'playbook pembayaran');
        });

        $response->assertViewHas('aiBenchmarkSummary', function (array $summary): bool {
            return (string) ($summary['latest_status'] ?? '') === 'pass'
                && abs((float) ($summary['latest_pass_rate_percent'] ?? 0) - 92.0) < 0.001
                && abs((float) ($summary['average_pass_rate'] ?? 0) - 81.0) < 0.001
                && (int) ($summary['days_with_report'] ?? 0) === 2
                && (int) ($summary['failed_days'] ?? 0) === 1
                && (int) ($summary['window_days'] ?? 0) === 7;
        });

        $response->assertViewHas('aiBenchmarkTrend7d', function (array $trend): bool {
            $trendCollection = collect($trend);

            return $trendCollection->count() === 7
                && $trendCollection->where('status', 'pass')->count() === 1
                && $trendCollection->where('status', 'fail')->count() === 1
                && $trendCollection->where('status', 'missing')->count() === 5;
        });

        $response->assertViewHas('benchmarkWindowDays', fn(int $windowDays): bool => $windowDays === 7);
        $response->assertViewHas('benchmarkWindowOptions', fn(array $windowOptions): bool => $windowOptions === [7, 14, 30]);

        $response14Days = $this->actingAs($admin)->get(route('admin.dashboard', ['benchmark_days' => 14]));

        $response14Days->assertOk();
        $response14Days->assertViewHas('benchmarkWindowDays', fn(int $windowDays): bool => $windowDays === 14);
        $response14Days->assertViewHas('aiBenchmarkTrend7d', function (array $trend): bool {
            $trendCollection = collect($trend);

            return $trendCollection->count() === 14
                && $trendCollection->where('status', 'pass')->count() === 1
                && $trendCollection->where('status', 'fail')->count() === 1
                && $trendCollection->where('status', 'missing')->count() === 12;
        });

        $response->assertSee('proof=uploaded');
        $response->assertSee('age_bucket=sla_overdue');
        $response->assertSee('Trend 7 Hari (Mini Chart)');
        $response->assertSee('Feedback AI Assistant (7 Hari)');
        $response->assertSee('Breakdown per Reason Code');
        $response->assertSee('Auto-Prioritization Root Cause per Intent');
        $response->assertSee('Benchmark AI Harian (7 Hari)');
        $response14Days->assertSee('Benchmark AI Harian (14 Hari)');
    }

    private function writeBenchmarkReport(
        \Illuminate\Support\Carbon $generatedAt,
        string $status,
        float $passRatePercent,
        float $thresholdPercent,
    ): void {
        $report = [
            'version' => 'ai-benchmark-v1',
            'generated_at' => $generatedAt->toISOString(),
            'status' => $status,
            'summary' => [
                'pass_rate_percent' => $passRatePercent,
                'threshold_percent' => $thresholdPercent,
                'started_at' => $generatedAt->copy()->subMinutes(3)->toISOString(),
                'finished_at' => $generatedAt->toISOString(),
            ],
            'results' => [],
        ];

        $fileName = 'ai-benchmark-' . $generatedAt->format('Ymd-His') . '.json';
        $outputPath = storage_path('app/ai-benchmarks/' . $fileName);

        File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
