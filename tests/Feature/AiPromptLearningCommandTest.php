<?php

namespace Tests\Feature;

use App\Models\AiAssistantFeedback;
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
}
