<?php

namespace Tests\Feature;

use App\Models\AiAssistantSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantSessionMemoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.ai.assistant_enabled', true);
        config()->set('services.ai.provider', 'rule_based');
    }

    public function test_ai_chat_persists_multi_turn_session_memory_in_database(): void
    {
        $sessionId = 'sess-memory-db-001';

        $firstResponse = $this->postJson(route('api.ai.chat'), [
            'session_id' => $sessionId,
            'message' => 'Cara checkout di website ini bagaimana?',
            'context' => [
                'channel' => 'storefront_widget',
                'page_title' => 'Katalog Produk',
                'page_path' => '/katalog',
            ],
        ]);

        $firstResponse->assertOk();

        $secondResponse = $this->postJson(route('api.ai.chat'), [
            'session_id' => $sessionId,
            'message' => 'Kalau set alamat default itu di mana?',
            'context' => [
                'channel' => 'storefront_widget',
                'page_title' => 'Profil',
                'page_path' => '/profile',
            ],
        ]);

        $secondResponse->assertOk();

        $historyItems = (array) $secondResponse->json('data.conversation_history');
        $joinedHistory = collect($historyItems)
            ->pluck('text')
            ->implode(' ');

        $this->assertStringContainsString('Cara checkout di website ini bagaimana?', $joinedHistory);

        $this->assertDatabaseHas('ai_assistant_sessions', [
            'session_id' => $sessionId,
        ]);

        $this->assertDatabaseCount('ai_assistant_messages', 4);

        $session = AiAssistantSession::query()->where('session_id', $sessionId)->first();
        $this->assertNotNull($session);
        $this->assertSame('/profile', $session->last_page_path);
        $this->assertGreaterThanOrEqual(2, (int) $session->turns_count);
    }

    public function test_ai_chat_bootstraps_database_memory_from_frontend_history_only_once(): void
    {
        $sessionId = 'sess-memory-bootstrap-001';

        $response = $this->postJson(route('api.ai.chat'), [
            'session_id' => $sessionId,
            'message' => 'Lanjut, setelah itu gimana?',
            'history' => [
                [
                    'role' => 'user',
                    'text' => 'Halo kak, bisa bantu?',
                ],
                [
                    'role' => 'assistant',
                    'text' => 'Bisa banget kak, mau tanya apa?',
                ],
            ],
            'context' => [
                'channel' => 'storefront_widget',
                'page_title' => 'Katalog Produk',
                'page_path' => '/katalog',
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('ai_assistant_messages', 4);

        $historyItems = (array) $response->json('data.conversation_history');
        $joinedHistory = collect($historyItems)
            ->pluck('text')
            ->implode(' ');

        $this->assertStringContainsString('Halo kak, bisa bantu?', $joinedHistory);
    }
}
