<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BayarGgIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_checkout_with_bayargg_creates_gateway_invoice_and_redirects_to_tracking(): void
    {
        $this->configureBayarGg();

        Http::fake([
            'https://www.bayar.gg/api/create-payment.php' => Http::response([
                'success' => true,
                'data' => [
                    'invoice_id' => 'PAY-admin-TEST123',
                    'payment_url' => 'https://www.bayar.gg/pay?invoice=PAY-admin-TEST123',
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(30)->format('Y-m-d H:i:s'),
                ],
            ], 200),
        ]);

        [$customer, $product] = $this->createCheckoutFixtures();

        $response = $this
            ->actingAs($customer)
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
                'payment_method' => 'bayargg',
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '081200001111',
                'address_label' => 'Rumah',
                'recipient_name' => $customer->name,
                'address_phone' => '081200001111',
                'address_line' => 'Jl. Bayar.gg No. 7',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40111',
            ]);

        $createdOrder = Order::query()->latest('id')->first();

        $this->assertNotNull($createdOrder);
        $response->assertRedirect(route('home.tracking.show', $createdOrder->order_code));

        $latestPayment = Payment::query()->where('order_id', $createdOrder->id)->latest('id')->first();
        $this->assertNotNull($latestPayment);
        $this->assertSame('bayargg', $latestPayment->method);
        $this->assertSame('bayargg', $latestPayment->gateway_provider);
        $this->assertSame('PAY-admin-TEST123', $latestPayment->gateway_invoice_id);
        $this->assertSame('https://www.bayar.gg/pay?invoice=PAY-admin-TEST123', $latestPayment->gateway_payment_url);

        $this->assertSame('pending', $latestPayment->status);
        $this->assertSame('pending', $createdOrder->fresh()->payment_status);
    }

    public function test_bayargg_webhook_marks_payment_as_paid_when_signature_is_valid(): void
    {
        $this->configureBayarGg();

        $customer = User::factory()->create();

        $order = Order::create([
            'order_code' => 'ORD-ARIP-' . now()->format('Ymd') . '-WBH001',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081200009999',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => 50000,
            'shipping_cost' => 5000,
            'discount_amount' => 0,
            'total_amount' => 55000,
            'placed_at' => now(),
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-WBH001',
            'method' => 'bayargg',
            'gateway_provider' => 'bayargg',
            'gateway_invoice_id' => 'PAY-admin-WEBHOOK001',
            'amount' => 55000,
            'status' => 'pending',
        ]);

        $payload = [
            'event' => 'payment.paid',
            'invoice_id' => 'PAY-admin-WEBHOOK001',
            'status' => 'paid',
            'amount' => 55000,
            'final_amount' => 55123,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'timestamp' => now()->timestamp,
            'paid_reff_num' => 'TRX-001',
        ];

        $timestamp = (string) now()->timestamp;
        $signature = $this->signWebhookPayload($payload, $timestamp);

        $response = $this
            ->withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
            ])
            ->postJson(route('api.webhooks.bayar-gg'), $payload);

        $response->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_regenerate_bayargg_link_creates_new_payment_without_losing_old_invoice_mapping(): void
    {
        $this->configureBayarGg();

        Http::fake([
            'https://www.bayar.gg/api/create-payment.php' => Http::response([
                'success' => true,
                'data' => [
                    'invoice_id' => 'PAY-admin-NEW001',
                    'payment_url' => 'https://www.bayar.gg/pay?invoice=PAY-admin-NEW001',
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(30)->format('Y-m-d H:i:s'),
                ],
            ], 200),
        ]);

        $customer = User::factory()->create();

        [$order, $existingPayment] = $this->createBayarGgOrderWithPayment(
            $customer,
            paymentOverrides: [
                'gateway_invoice_id' => 'PAY-admin-OLD001',
                'gateway_payment_url' => 'https://www.bayar.gg/pay?invoice=PAY-admin-OLD001',
                'gateway_status' => 'pending',
                'status' => 'pending',
            ],
        );

        $response = $this
            ->actingAs($customer)
            ->from(route('home.tracking.show', $order->order_code))
            ->post(route('home.tracking.bayargg.regenerate', $order->order_code));

        $response->assertRedirect(route('home.tracking.show', $order->order_code));

        $payments = Payment::query()
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $payments);

        $oldPayment = $payments->firstWhere('id', $existingPayment->id);
        $this->assertNotNull($oldPayment);
        $this->assertSame('PAY-admin-OLD001', $oldPayment->gateway_invoice_id);
        $this->assertSame('https://www.bayar.gg/pay?invoice=PAY-admin-OLD001', $oldPayment->gateway_payment_url);

        $latestPayment = $payments->last();
        $this->assertNotNull($latestPayment);
        $this->assertNotSame($existingPayment->id, $latestPayment->id);
        $this->assertSame('PAY-admin-NEW001', $latestPayment->gateway_invoice_id);
        $this->assertSame('https://www.bayar.gg/pay?invoice=PAY-admin-NEW001', $latestPayment->gateway_payment_url);
    }

    public function test_bayargg_webhook_rejects_replay_request(): void
    {
        $this->configureBayarGg();

        $customer = User::factory()->create();
        [$order, $payment] = $this->createBayarGgOrderWithPayment(
            $customer,
            paymentOverrides: [
                'gateway_invoice_id' => 'PAY-admin-REPLAY001',
            ],
        );

        $payload = [
            'event' => 'payment.paid',
            'invoice_id' => 'PAY-admin-REPLAY001',
            'status' => 'paid',
            'amount' => 55000,
            'final_amount' => 55000,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'timestamp' => now()->timestamp,
            'paid_reff_num' => 'TRX-REPLAY-001',
        ];

        $timestamp = (string) now()->timestamp;
        $signature = $this->signWebhookPayload($payload, $timestamp);

        $this
            ->withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
            ])
            ->postJson(route('api.webhooks.bayar-gg'), $payload)
            ->assertOk();

        $this
            ->withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
            ])
            ->postJson(route('api.webhooks.bayar-gg'), $payload)
            ->assertStatus(401);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_expired_callback_for_old_invoice_does_not_downgrade_paid_order_status(): void
    {
        $this->configureBayarGg();

        $customer = User::factory()->create();
        [$order, $oldPayment] = $this->createBayarGgOrderWithPayment(
            $customer,
            paymentOverrides: [
                'gateway_invoice_id' => 'PAY-admin-OLD-EXPIRED',
                'status' => 'pending',
            ],
        );

        $latestPaidPayment = Payment::create([
            'order_id' => $order->id,
            'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-NEWPAID',
            'method' => 'bayargg',
            'gateway_provider' => 'bayargg',
            'gateway_invoice_id' => 'PAY-admin-NEW-PAID',
            'amount' => 55000,
            'status' => 'paid',
            'gateway_status' => 'paid',
            'paid_at' => now(),
        ]);

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        $payload = [
            'event' => 'payment.expired',
            'invoice_id' => 'PAY-admin-OLD-EXPIRED',
            'status' => 'expired',
            'amount' => 55000,
            'final_amount' => 55000,
            'timestamp' => now()->timestamp,
        ];

        $timestamp = (string) now()->timestamp;
        $signature = $this->signWebhookPayload($payload, $timestamp);

        $this
            ->withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
            ])
            ->postJson(route('api.webhooks.bayar-gg'), $payload)
            ->assertOk();

        $this->assertSame('failed', $oldPayment->fresh()->status);
        $this->assertSame('paid', $latestPaidPayment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_paid_callback_after_order_cancelled_keeps_order_cancelled_and_marks_payment_paid(): void
    {
        $this->configureBayarGg();

        $customer = User::factory()->create();
        [$order, $payment] = $this->createBayarGgOrderWithPayment(
            $customer,
            orderOverrides: [
                'status' => 'cancelled',
                'payment_status' => 'failed',
                'paid_at' => null,
            ],
            paymentOverrides: [
                'gateway_invoice_id' => 'PAY-admin-CANCELLED-LATE',
                'status' => 'pending',
            ],
        );

        $payload = [
            'event' => 'payment.paid',
            'invoice_id' => 'PAY-admin-CANCELLED-LATE',
            'status' => 'paid',
            'amount' => 55000,
            'final_amount' => 55000,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'timestamp' => now()->timestamp,
            'paid_reff_num' => 'TRX-LATE-PAID-001',
        ];

        $timestamp = (string) now()->timestamp;
        $signature = $this->signWebhookPayload($payload, $timestamp);

        $this
            ->withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
            ])
            ->postJson(route('api.webhooks.bayar-gg'), $payload)
            ->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
    }

    private function configureBayarGg(): void
    {
        config()->set('services.bayargg.base_url', 'https://www.bayar.gg/api');
        config()->set('services.bayargg.api_key', 'test-api-key');
        config()->set('services.bayargg.webhook_secret', 'whsec-test-key');
        config()->set('services.bayargg.webhook_tolerance_seconds', 300);
        config()->set('services.bayargg.webhook_replay_ttl_seconds', 600);
        config()->set('services.bayargg.payment_method', 'qris');
        config()->set('services.bayargg.use_qris_converter', false);
        config()->set('services.bayargg.timeout', 15);
    }

    private function signWebhookPayload(array $payload, string $timestamp): string
    {
        $signaturePayload = (string) ($payload['invoice_id'] ?? '')
            . '|'
            . strtolower((string) ($payload['status'] ?? ''))
            . '|'
            . (string) ($payload['final_amount'] ?? '')
            . '|'
            . $timestamp;

        return hash_hmac('sha256', $signaturePayload, 'whsec-test-key');
    }

    /**
     * @return array{0: Order, 1: Payment}
     */
    private function createBayarGgOrderWithPayment(
        User $customer,
        array $orderOverrides = [],
        array $paymentOverrides = [],
    ): array {
        $order = Order::create(array_merge([
            'order_code' => 'ORD-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081200009999',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => 50000,
            'shipping_cost' => 5000,
            'discount_amount' => 0,
            'total_amount' => 55000,
            'placed_at' => now(),
            'paid_at' => null,
        ], $orderOverrides));

        $payment = Payment::create(array_merge([
            'order_id' => $order->id,
            'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'method' => 'bayargg',
            'gateway_provider' => 'bayargg',
            'gateway_invoice_id' => 'PAY-admin-BASE001',
            'amount' => 55000,
            'status' => 'pending',
            'gateway_status' => 'pending',
            'paid_at' => null,
        ], $paymentOverrides));

        return [$order, $payment];
    }

    private function createCheckoutFixtures(): array
    {
        $customer = User::factory()->create([
            'email' => 'bayargg-checkout-user@example.com',
        ]);

        $category = Category::create([
            'name' => 'Payment Gateway Category',
            'slug' => 'payment-gateway-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Stop Kontak Premium',
            'slug' => 'stop-kontak-premium',
            'description' => 'Produk test Bayar.gg checkout.',
            'price' => 30000,
            'stock' => 10,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);

        return [$customer, $product];
    }
}
