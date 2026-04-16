<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiAssistantChatEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.ai.assistant_enabled', true);
        config()->set('services.ai.provider', 'rule_based');
        config()->set('services.ai.model_fast', 'gemini-2.5-flash');
        config()->set('services.ai.model_fallback', 'deepseek-chat');
        config()->set('services.ai.gemini_api_key', null);
        config()->set('services.ai.deepseek_api_key', null);
    }

    public function test_ai_chat_returns_faq_answer_for_shipping_question(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-faq-001',
            'message' => 'Ongkir berapa untuk checkout?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'faq');
        $response->assertJsonPath('used_tools.0', 'FaqAnswerTool');

        $this->assertStringContainsString('Ongkir', (string) $response->json('reply'));
    }

    public function test_ai_chat_returns_order_tracking_for_authenticated_order_owner(): void
    {
        [$customer, $order] = $this->createOrderFixture();
        Sanctum::actingAs($customer);

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-track-owner-001',
            'message' => 'Tolong cek status pesanan ' . $order->order_code,
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'order_tracking');
        $response->assertJsonPath('used_tools.0', 'OrderTrackingTool');
        $response->assertJsonPath('data.requires_verification', false);
        $response->assertJsonPath('data.order.order_code', $order->order_code);
    }

    public function test_ai_chat_requires_verification_for_guest_order_tracking_request(): void
    {
        [, $order] = $this->createOrderFixture();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-track-guest-001',
            'message' => 'Cek pesanan ' . $order->order_code,
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'order_tracking');
        $response->assertJsonPath('data.requires_verification', true);
        $response->assertJsonPath('data.order', null);
    }

    public function test_ai_chat_allows_guest_tracking_when_email_matches_order(): void
    {
        [$customer, $order] = $this->createOrderFixture();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-track-guest-verified-001',
            'message' => 'Status pesanan ' . $order->order_code,
            'customer_email' => $customer->email,
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'order_tracking');
        $response->assertJsonPath('data.requires_verification', false);
        $response->assertJsonPath('data.order.order_code', $order->order_code);
    }

    public function test_ai_chat_returns_product_recommendation_by_budget(): void
    {
        $this->createProductFixtures();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-reco-001',
            'message' => 'Minta rekomendasi kabel budget 50000',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'product_recommendation');
        $response->assertJsonPath('used_tools.0', 'ProductRecommendationTool');

        $products = $response->json('data.products');
        $this->assertIsArray($products);
        $this->assertNotEmpty($products);

        foreach ($products as $product) {
            $this->assertLessThanOrEqual(50000, (int) $product['price']);
        }
    }

    public function test_ai_chat_understands_natural_budget_phrase_for_lampu_kamar_tidur(): void
    {
        $this->createProductFixtures();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-reco-natural-001',
            'message' => 'saya punya uang 40ribu, kira-kira lampu yang cocok untuk ruangan kamar tidur apa',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'product_recommendation');

        $products = $response->json('data.products');
        $this->assertIsArray($products);
        $this->assertNotEmpty($products);

        foreach ($products as $product) {
            $this->assertLessThanOrEqual(40000, (int) $product['price']);
        }

        $topProductName = strtolower((string) data_get($products, '0.name', ''));
        $this->assertStringContainsString('lampu', $topProductName);
    }

    public function test_ai_chat_uses_gemini_provider_when_enabled(): void
    {
        $this->configureExternalAiProviderForTest([
            'provider' => 'gemini',
            'model_fast' => 'gemini-2.5-flash',
            'model_fallback' => 'deepseek-chat',
            'gemini_api_key' => 'test-gemini-key',
            'deepseek_api_key' => 'test-deepseek-key',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Ini jawaban dari Gemini.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-gemini-001',
            'message' => 'Apa metode pembayaran yang tersedia?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('reply', 'Ini jawaban dari Gemini.');
        $response->assertJsonPath('data.llm.provider', 'gemini');
        $response->assertJsonPath('data.llm.model', 'gemini-2.5-flash');
        $response->assertJsonPath('data.llm.status', 'primary_success');
        $response->assertJsonPath('data.llm.fallback_used', false);
        $response->assertJsonPath('data.llm.attempts.0.success', true);
    }

    public function test_ai_chat_falls_back_to_deepseek_when_gemini_fails(): void
    {
        $this->configureExternalAiProviderForTest([
            'provider' => 'gemini',
            'model_fast' => 'gemini-2.5-flash',
            'model_fallback' => 'deepseek-chat',
            'gemini_api_key' => 'test-gemini-key',
            'deepseek_api_key' => 'test-deepseek-key',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Upstream unavailable'],
            ], 500),
            'https://api.deepseek.com/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Ini jawaban fallback dari DeepSeek.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-fallback-001',
            'message' => 'Bagaimana cara pembayaran?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('reply', 'Ini jawaban fallback dari DeepSeek.');
        $response->assertJsonPath('data.llm.provider', 'deepseek');
        $response->assertJsonPath('data.llm.model', 'deepseek-chat');
        $response->assertJsonPath('data.llm.status', 'fallback_success');
        $response->assertJsonPath('data.llm.fallback_used', true);
        $response->assertJsonPath('data.llm.attempts.0.provider', 'gemini');
        $response->assertJsonPath('data.llm.attempts.0.success', false);
        $response->assertJsonPath('data.llm.attempts.1.provider', 'deepseek');
        $response->assertJsonPath('data.llm.attempts.1.success', true);
    }

    public function test_ai_chat_keeps_rule_reply_and_exposes_failure_metadata_when_all_llm_providers_fail(): void
    {
        $this->configureExternalAiProviderForTest([
            'provider' => 'gemini',
            'model_fast' => 'gemini-2.5-flash',
            'model_fallback' => 'deepseek-chat',
            'gemini_api_key' => 'test-gemini-key',
            'deepseek_api_key' => 'test-deepseek-key',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Gemini down'],
            ], 500),
            'https://api.deepseek.com/chat/completions' => Http::response([
                'error' => ['message' => 'DeepSeek down'],
            ], 503),
        ]);

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-fallback-failed-001',
            'message' => 'Ongkir berapa?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'faq');
        $this->assertStringContainsString('Ongkir', (string) $response->json('reply'));
        $response->assertJsonPath('data.llm.provider', 'deepseek');
        $response->assertJsonPath('data.llm.status', 'fallback_failed');
        $response->assertJsonPath('data.llm.fallback_used', false);
        $response->assertJsonPath('data.llm.attempts.0.provider', 'gemini');
        $response->assertJsonPath('data.llm.attempts.0.success', false);
        $response->assertJsonPath('data.llm.attempts.1.provider', 'deepseek');
        $response->assertJsonPath('data.llm.attempts.1.success', false);
    }

    private function createOrderFixture(): array
    {
        $customer = User::factory()->create([
            'email' => 'ai-tracking-user@example.com',
        ]);

        $order = Order::create([
            'order_code' => 'ORD-ARIP-' . now()->format('Ymd') . '-AI001A',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081299998888',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => 45000,
            'shipping_cost' => 5000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'placed_at' => now(),
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-AI001A',
            'method' => 'bayargg',
            'gateway_provider' => 'bayargg',
            'gateway_invoice_id' => 'PAY-admin-AI001A',
            'gateway_payment_url' => 'https://www.bayar.gg/pay?invoice=PAY-admin-AI001A',
            'amount' => 50000,
            'status' => 'pending',
            'gateway_status' => 'pending',
        ]);

        return [$customer, $order];
    }

    private function createProductFixtures(): void
    {
        $category = Category::create([
            'name' => 'Kategori AI Rekomendasi',
            'slug' => 'kategori-ai-rekomendasi',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Lampu LED Warm White 9W',
            'slug' => 'lampu-led-warm-white-9w-ai',
            'description' => 'Lampu LED hemat energi yang nyaman untuk kamar tidur.',
            'price' => 38000,
            'stock' => 12,
            'unit' => 'pcs',
            'specifications' => [
                'watt' => '9W',
                'warna' => 'Warm White',
                'cocok_untuk' => 'Kamar Tidur',
            ],
            'is_active' => true,
            'is_electronic' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Kabel NYA 2.5mm',
            'slug' => 'kabel-nya-2-5mm-ai',
            'description' => 'Kabel berkualitas untuk instalasi rumah.',
            'price' => 35000,
            'stock' => 20,
            'unit' => 'roll',
            'is_active' => true,
            'is_electronic' => false,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Stop Kontak Basic 2 Lubang',
            'slug' => 'stop-kontak-basic-ai',
            'description' => 'Stop kontak dinding untuk kebutuhan umum.',
            'price' => 28000,
            'stock' => 24,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'MCB Premium 32A',
            'slug' => 'mcb-premium-32a-ai',
            'description' => 'MCB premium untuk kebutuhan panel listrik.',
            'price' => 75000,
            'stock' => 20,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);
    }

    private function configureExternalAiProviderForTest(array $overrides = []): void
    {
        $config = array_merge([
            'assistant_enabled' => true,
            'provider' => 'gemini',
            'model_fast' => 'gemini-2.5-flash',
            'model_fallback' => 'deepseek-chat',
            'request_timeout' => 20,
            'max_output_tokens' => 500,
            'gemini_api_key' => 'test-gemini-key',
            'deepseek_api_key' => 'test-deepseek-key',
        ], $overrides);

        foreach ($config as $key => $value) {
            config()->set('services.ai.' . $key, $value);
        }
    }
}
