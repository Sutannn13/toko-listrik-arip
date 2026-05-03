<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiAssistantChatEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config()->set('services.ai.assistant_enabled', true);
        config()->set('services.ai.provider', 'rule_based');
        config()->set('services.ai.model_fast', 'gemini-2.5-flash');
        config()->set('services.ai.model_fallback', 'deepseek-chat');
        config()->set('services.ai.prompt_version', 'v2');
        config()->set('services.ai.daily_budget_idr', 50000);
        config()->set('services.ai.estimated_cost_per_request_idr', 350);
        config()->set('services.ai.complex_case_enabled', true);
        config()->set('services.ai.complex_case_high_threshold', 65);
        config()->set('services.ai.complex_case_critical_threshold', 85);
        config()->set('services.ai.gemini_api_key', null);
        config()->set('services.ai.deepseek_api_key', null);
        config()->set('services.ai.web_search_enabled', false);
        config()->set('services.ai.web_search_endpoint', 'https://api.duckduckgo.com/');
        config()->set('services.ai.web_search_timeout', 8);
        config()->set('services.ai.web_search_max_results', 3);
    }

    public function test_ai_chat_returns_faq_answer_for_shipping_question(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-faq-001',
            'message' => 'Ongkir berapa untuk checkout?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'website_help');
        $response->assertJsonPath('used_tools.0', 'FaqAnswerTool');

        $this->assertStringContainsString('Ongkir', (string) $response->json('reply'));
    }

    public function test_ai_chat_routes_shipping_questions_to_website_help_faq(): void
    {
        $messages = [
            'Ongkir berapa?',
            'Berapa biaya kirim?',
            'Estimasi pengiriman berapa lama?',
        ];

        foreach ($messages as $index => $message) {
            $response = $this->postJson(route('api.ai.chat'), [
                'session_id' => 'sess-shipping-regression-' . $index,
                'message' => $message,
            ]);

            $response->assertOk();
            $response->assertJsonPath('intent', 'website_help');
            $response->assertJsonPath('used_tools.0', 'FaqAnswerTool');

            $this->assertStringContainsString('Ongkir', (string) $response->json('reply'));
        }
    }

    public function test_ai_chat_includes_page_context_and_history_in_response_data(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-context-001',
            'message' => 'Bagaimana cara menambahkan alamat pengiriman?',
            'history' => [
                [
                    'role' => 'user',
                    'text' => 'Halo kak',
                ],
                [
                    'role' => 'assistant',
                    'text' => 'Halo kak, mau dibantu apa hari ini?',
                ],
            ],
            'context' => [
                'channel' => 'storefront_widget',
                'page_title' => 'Profil Akun',
                'page_path' => '/profile',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.page_context.channel', 'storefront_widget');
        $response->assertJsonPath('data.page_context.page_path', '/profile');
        $response->assertJsonPath('data.conversation_history.0.role', 'user');
        $response->assertJsonPath('data.conversation_history.1.role', 'assistant');
    }

    public function test_ai_chat_rejects_invalid_history_role(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-invalid-history-001',
            'message' => 'Cek dong',
            'history' => [
                [
                    'role' => 'system',
                    'text' => 'Unauthorized role',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['history.0.role']);
    }

    public function test_ai_chat_intent_matrix_across_page_contexts(): void
    {
        $this->createProductFixtures();
        [$customer, $order] = $this->createOrderFixture();

        $testCases = [
            [
                'message' => 'Rekomendasi lampu untuk kamar tidur budget 40rb',
                'expected_intent' => 'product_recommendation',
                'context' => [
                    'channel' => 'storefront_widget',
                    'page_title' => 'Katalog Produk',
                    'page_path' => '/katalog',
                ],
            ],
            [
                'message' => 'Cara set alamat default untuk checkout?',
                'expected_intent' => 'website_help',
                'context' => [
                    'channel' => 'storefront_widget',
                    'page_title' => 'Kelola Alamat',
                    'page_path' => '/profile/addresses',
                ],
            ],
            [
                'message' => 'Alamat toko dimana?',
                'expected_intent' => 'store_info',
                'context' => [
                    'channel' => 'storefront_widget',
                    'page_title' => 'Privacy Policy',
                    'page_path' => '/privacy-policy',
                ],
            ],
            [
                'message' => 'Cek pesanan ' . $order->order_code,
                'expected_intent' => 'order_tracking',
                'customer_email' => $customer->email,
                'context' => [
                    'channel' => 'storefront_widget',
                    'page_title' => 'Cek Pesanan',
                    'page_path' => '/cek-pesanan',
                ],
            ],
            [
                'message' => 'Siapa presiden sekarang?',
                'expected_intent' => 'off_topic',
                'context' => [
                    'channel' => 'storefront_widget',
                    'page_title' => 'Checkout',
                    'page_path' => '/checkout',
                ],
            ],
        ];

        foreach ($testCases as $index => $testCase) {
            $response = $this->postJson(route('api.ai.chat'), [
                'session_id' => 'sess-intent-matrix-' . $index,
                'message' => $testCase['message'],
                'customer_email' => $testCase['customer_email'] ?? null,
                'context' => $testCase['context'],
            ]);

            $response->assertOk();
            $response->assertJsonPath('intent', $testCase['expected_intent']);
            $response->assertJsonPath('data.page_context.page_path', $testCase['context']['page_path']);
        }
    }

    public function test_ai_chat_detects_complex_multi_issue_case_and_exposes_resolution_profile(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-complex-case-001',
            'message' => 'Produk lampu saya rusak, bukti pembayaran juga ditolak, dan pesanan belum dikirim. Saya panik dan kecewa banget, tolong langkah jelasnya satu per satu sekarang.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'troubleshooting');
        $response->assertJsonPath('data.complex_case_profile.needs_tutorial_mode', true);
        $response->assertJsonPath('data.complex_case_profile.case_weight', 'critical');

        $usedTools = (array) $response->json('used_tools');
        $this->assertContains('ComplexCaseCoach', $usedTools);

        $priorityActions = (array) $response->json('data.complex_case_profile.priority_actions');
        $this->assertNotEmpty($priorityActions);
    }

    public function test_ai_chat_prioritizes_troubleshooting_when_order_status_phrase_is_part_of_multi_issue_complaint(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-troubleshoot-priority-001',
            'message' => 'Bukti pembayaran saya ditolak padahal saldo kepotong, status order belum diproses, dan saya butuh solusi sekarang.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'troubleshooting');
    }

    public function test_ai_chat_routes_lampu_mati_to_troubleshooting_with_safety_warning(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-electrical-lamp-001',
            'message' => 'Lampu mati di kamar, harus cek apa?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'troubleshooting');
        $response->assertJsonPath('used_tools.0', 'FaqAnswerTool');
        $response->assertJsonPath('data.source_key', 'faq.troubleshoot.electrical_lamp');

        $reply = strtolower((string) $response->json('reply'));
        $this->assertStringContainsString('peringatan aman', $reply);
        $this->assertStringContainsString('matikan', $reply);
        $this->assertStringContainsString('saklar', $reply);
    }

    public function test_ai_chat_routes_repeated_mcb_trips_to_technician_or_admin(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-electrical-mcb-001',
            'message' => 'MCB turun terus, gimana ya?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'troubleshooting');
        $response->assertJsonPath('data.source_key', 'faq.troubleshoot.electrical_danger');

        $reply = strtolower((string) $response->json('reply'));
        $this->assertStringContainsString('matikan mcb', $reply);
        $this->assertStringContainsString('teknisi listrik', $reply);
        $this->assertStringContainsString('admin toko', $reply);
    }

    public function test_ai_chat_escalates_hot_cable_and_burning_smell_without_internal_repair_steps(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-electrical-danger-001',
            'message' => 'Kabel panas dan bau gosong dekat stop kontak.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'troubleshooting');
        $response->assertJsonPath('data.source_key', 'faq.troubleshoot.electrical_danger');

        $reply = strtolower((string) $response->json('reply'));
        $this->assertStringContainsString('matikan mcb/listrik', $reply);
        $this->assertStringContainsString('teknisi listrik', $reply);
        $this->assertStringContainsString('admin toko', $reply);
        $this->assertStringNotContainsString('bongkar', $reply);
    }

    public function test_ai_chat_handles_after_hours_purchase_policy_as_website_help(): void
    {
        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-after-hours-policy-001',
            'message' => 'Kalau saya beli barang melewati jam operasional, nanti akan gimana prosesnya?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'website_help');

        $reply = strtolower((string) $response->json('reply'));
        $this->assertStringContainsString('24 jam', $reply);
        $this->assertStringContainsString('jam operasional', $reply);
    }

    public function test_ai_chat_injects_complex_case_profile_into_provider_prompt(): void
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
                                ['text' => 'Tenang kak, ini langkah pemulihan paling aman buat kasus kakak.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-complex-case-provider-001',
            'message' => 'Saya kecewa, bukti transfer ditolak dan paket belum dikirim. Tolong bantu langkah jelasnya.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'troubleshooting');
        $response->assertJsonPath('data.llm.provider', 'gemini');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            $requestPayload = $request->data();
            $compiledPrompt = (string) data_get($requestPayload, 'contents.0.parts.0.text', '');

            return str_contains($compiledPrompt, '[Complex Case Intelligence Profile]')
                && str_contains($compiledPrompt, 'case_weight')
                && str_contains($compiledPrompt, 'priority_actions');
        });
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

        $this->assertStringContainsString($order->order_code, (string) $response->json('reply'));
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

    public function test_ai_chat_keeps_product_recommendation_for_price_and_budget_queries(): void
    {
        $this->createProductFixtures();

        $messages = [
            'Berapa harga kabel NYA?',
            'Minta rekomendasi kabel budget 50000',
        ];

        foreach ($messages as $index => $message) {
            $response = $this->postJson(route('api.ai.chat'), [
                'session_id' => 'sess-product-regression-' . $index,
                'message' => $message,
            ]);

            $response->assertOk();
            $response->assertJsonPath('intent', 'product_recommendation');
            $response->assertJsonPath('used_tools.0', 'ProductRecommendationTool');

            $products = $response->json('data.products');
            $this->assertIsArray($products);
            $this->assertNotEmpty($products);
        }
    }

    public function test_ai_chat_does_not_hallucinate_unknown_product_price_or_stock(): void
    {
        $this->createProductFixtures();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-product-missing-001',
            'message' => 'Berapa harga kabel Narnia 9x9? Stoknya ada?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'product_recommendation');
        $response->assertJsonPath('data.recommendation_meta.match_strategy', 'none');
        $response->assertJsonPath('data.recommendation_meta.catalog_guard', 'unmatched_specific_product');

        $this->assertSame([], $response->json('data.products'));

        $reply = strtolower((string) $response->json('reply'));
        $this->assertStringContainsString('belum ditemukan', $reply);
        $this->assertStringContainsString('katalog/database', $reply);
        $this->assertStringContainsString('admin toko', $reply);
        $this->assertStringNotContainsString('rp ', $reply);
        $this->assertStringNotContainsString('kabel nya', $reply);
        $this->assertStringNotContainsString('lampu led', $reply);
    }

    public function test_ai_chat_keeps_specific_product_query_database_backed(): void
    {
        $this->createProductFixtures();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-product-specific-db-001',
            'message' => 'Berapa harga Kabel NYA 2.5mm?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'product_recommendation');
        $response->assertJsonPath('data.products.0.name', 'Kabel NYA 2.5mm');
        $response->assertJsonPath('data.products.0.price', 35000);

        $reply = (string) $response->json('reply');
        $this->assertStringContainsString('Kabel NYA 2.5mm', $reply);
        $this->assertStringContainsString('Rp 35.000', $reply);
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

    public function test_ai_chat_understands_description_driven_product_query(): void
    {
        $this->createProductFixtures();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-reco-description-001',
            'message' => 'Saya cari product A dari deskripsi: lampu LED hemat energi yang nyaman untuk kamar tidur.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'product_recommendation');

        $products = $response->json('data.products');
        $this->assertIsArray($products);
        $this->assertNotEmpty($products);

        $topProductName = strtolower((string) data_get($products, '0.name', ''));
        $this->assertStringContainsString('lampu', $topProductName);

        $response->assertJsonPath('data.recommendation_meta.description_driven', true);
    }

    public function test_ai_chat_uses_web_search_fallback_for_explicit_search_engine_request(): void
    {
        config()->set('services.ai.web_search_enabled', true);
        config()->set('services.ai.web_search_max_results', 2);

        Http::fake([
            'https://api.duckduckgo.com/*' => Http::response([
                'Heading' => 'Product A',
                'AbstractText' => 'Product A adalah lampu LED hemat energi dengan umur pakai panjang.',
                'AbstractURL' => 'https://example.com/product-a',
                'RelatedTopics' => [
                    [
                        'Text' => 'Product A - Cocok untuk penggunaan rumah tangga.',
                        'FirstURL' => 'https://example.com/product-a-home',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-reco-websearch-001',
            'message' => 'Tolong search product A di search engine lalu jelaskan deskripsinya.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'product_recommendation');
        $response->assertJsonPath('data.web_search.status', 'ok');
        $response->assertJsonPath('data.web_search.results.0.url', 'https://example.com/product-a');

        $usedTools = (array) $response->json('used_tools');
        $this->assertContains('WebProductSearchTool', $usedTools);

        $this->assertStringContainsString('Referensi tambahan dari search engine', (string) $response->json('reply'));
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
        $response->assertJsonPath('intent', 'website_help');
        $this->assertStringContainsString('Ongkir', (string) $response->json('reply'));
        $response->assertJsonPath('data.llm.provider', 'deepseek');
        $response->assertJsonPath('data.llm.status', 'fallback_failed');
        $response->assertJsonPath('data.llm.fallback_used', false);
        $response->assertJsonPath('data.llm.attempts.0.provider', 'gemini');
        $response->assertJsonPath('data.llm.attempts.0.success', false);
        $response->assertJsonPath('data.llm.attempts.1.provider', 'deepseek');
        $response->assertJsonPath('data.llm.attempts.1.success', false);
    }

    public function test_ai_chat_skips_external_provider_for_order_tracking_due_to_privacy_guard(): void
    {
        [$customer, $order] = $this->createOrderFixture();
        Sanctum::actingAs($customer);

        $this->configureExternalAiProviderForTest([
            'provider' => 'gemini',
            'gemini_api_key' => 'test-gemini-key',
            'deepseek_api_key' => 'test-deepseek-key',
        ]);

        Http::fake();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-privacy-guard-001',
            'message' => 'Tolong cek status pesanan ' . $order->order_code,
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'order_tracking');
        $response->assertJsonPath('data.llm.status', 'privacy_guard_skipped');
        $response->assertJsonPath('data.llm.provider', 'rule_based');

        Http::assertNothingSent();
    }

    public function test_ai_chat_skips_external_provider_when_daily_budget_is_exhausted(): void
    {
        $this->configureExternalAiProviderForTest([
            'provider' => 'gemini',
            'gemini_api_key' => 'test-gemini-key',
            'deepseek_api_key' => 'test-deepseek-key',
            'daily_budget_idr' => 100,
            'estimated_cost_per_request_idr' => 250,
        ]);

        Http::fake();

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => 'sess-budget-guard-001',
            'message' => 'Cara bayar di website ini bagaimana?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.llm.status', 'budget_exhausted');
        $response->assertJsonPath('data.llm.provider', 'gemini');
        $response->assertJsonPath('data.llm.budget.guard_enabled', true);

        Http::assertNothingSent();
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
            'daily_budget_idr' => 50000,
            'estimated_cost_per_request_idr' => 350,
            'gemini_api_key' => 'test-gemini-key',
            'deepseek_api_key' => 'test-deepseek-key',
        ], $overrides);

        foreach ($config as $key => $value) {
            config()->set('services.ai.' . $key, $value);
        }
    }
}
