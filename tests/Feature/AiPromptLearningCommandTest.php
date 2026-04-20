<?php

namespace Tests\Feature;

use App\Models\AiAssistantFeedback;
use App\Models\AiPromptLearningRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiPromptLearningCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_learning_command_generates_prompt_rules_from_negative_feedback(): void
    {
        AiAssistantFeedback::query()->create([
            'session_id' => 'sess-learner-1',
            'intent' => 'website_help',
            'rating' => -1,
            'reason' => 'Jawaban terlalu umum, langkahnya kurang jelas.',
        ]);

        AiAssistantFeedback::query()->create([
            'session_id' => 'sess-learner-2',
            'intent' => 'website_help',
            'rating' => -1,
            'reason' => 'Tidak ada step by step untuk halaman profil alamat.',
        ]);

        AiAssistantFeedback::query()->create([
            'session_id' => 'sess-learner-3',
            'intent' => 'website_help',
            'rating' => -1,
            'reason' => 'Harusnya kasih alur yang lebih operasional.',
        ]);

        $this->artisan('ai:learn-feedback-rules --days=30 --min-signals=2')
            ->assertExitCode(0);

        $this->assertDatabaseHas('ai_prompt_learning_rules', [
            'rule_key' => 'negative_feedback:website_help',
            'intent' => 'website_help',
            'is_active' => true,
        ]);
    }

    public function test_ai_learning_command_uses_reason_code_and_runtime_signals_for_deeper_training(): void
    {
        AiAssistantFeedback::query()->create([
            'session_id' => 'sess-learner-runtime-1',
            'intent' => 'troubleshooting',
            'rating' => -1,
            'reason' => null,
            'reason_code' => 'not-helpful-timeout',
            'llm_status' => 'fallback_failed',
            'fallback_used' => true,
            'response_latency_ms' => 12000,
            'provider' => 'gemini',
        ]);

        AiAssistantFeedback::query()->create([
            'session_id' => 'sess-learner-runtime-2',
            'intent' => 'troubleshooting',
            'rating' => -1,
            'reason' => null,
            'reason_code' => 'not_helpful_slow_response',
            'llm_status' => 'primary_failed',
            'fallback_used' => false,
            'response_latency_ms' => 9000,
            'provider' => 'deepseek',
        ]);

        $this->artisan('ai:learn-feedback-rules --days=45 --min-signals=2')
            ->assertExitCode(0);

        $this->assertDatabaseHas('ai_prompt_learning_rules', [
            'rule_key' => 'negative_feedback:troubleshooting',
            'intent' => 'troubleshooting',
            'is_active' => true,
        ]);

        $rule = AiPromptLearningRule::query()
            ->where('rule_key', 'negative_feedback:troubleshooting')
            ->first();

        $this->assertNotNull($rule);

        $topReasonCodes = data_get($rule?->metrics, 'top_reason_codes', []);
        $this->assertIsArray($topReasonCodes);
        $this->assertContains('not_helpful_timeout', $topReasonCodes);

        $llmFailureRate = (float) data_get($rule?->metrics, 'quality_signals.llm_failure_rate_percent', 0.0);
        $this->assertGreaterThan(0.0, $llmFailureRate);
    }
}
