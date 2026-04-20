<?php

namespace Tests\Feature;

use App\Models\AiAssistantFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantFeedbackEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_feedback_endpoint_stores_feedback_payload(): void
    {
        $response = $this->postJson(route('api.ai.feedback'), [
            'session_id' => 'sess-feedback-001',
            'message_id' => 'msg-feedback-001',
            'intent' => 'faq',
            'intent_detected' => 'faq',
            'intent_resolved' => 'faq',
            'rating' => 1,
            'reason' => 'Jawabannya membantu.',
            'reason_code' => 'helpful_answer_quality',
            'response_latency_ms' => 842,
            'prompt_version' => 'v2',
            'rule_version' => 'rules:3:20260419190000',
            'response_source' => 'provider_rewrite',
            'feedback_version' => 2,
            'metadata' => [
                'provider' => 'gemini',
                'model' => 'gemini-2.5-flash',
                'fallback_used' => false,
                'status' => 'primary_success',
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Feedback AI berhasil disimpan.');

        $feedback = AiAssistantFeedback::query()->first();
        $this->assertNotNull($feedback);
        $this->assertSame('sess-feedback-001', $feedback->session_id);
        $this->assertSame('msg-feedback-001', $feedback->message_id);
        $this->assertSame('faq', $feedback->intent);
        $this->assertSame('faq', $feedback->intent_detected);
        $this->assertSame('faq', $feedback->intent_resolved);
        $this->assertSame(1, (int) $feedback->rating);
        $this->assertSame('Jawabannya membantu.', $feedback->reason);
        $this->assertSame('helpful_answer_quality', $feedback->reason_code);
        $this->assertSame(842, (int) $feedback->response_latency_ms);
        $this->assertSame('v2', $feedback->prompt_version);
        $this->assertSame('rules:3:20260419190000', $feedback->rule_version);
        $this->assertSame('provider_rewrite', $feedback->response_source);
        $this->assertSame(2, (int) $feedback->feedback_version);
        $this->assertSame('gemini', $feedback->provider);
        $this->assertSame('gemini-2.5-flash', $feedback->model);
        $this->assertSame('primary_success', $feedback->llm_status);
        $this->assertFalse((bool) $feedback->fallback_used);
        $this->assertSame('gemini', (string) data_get($feedback->metadata, 'provider'));
    }

    public function test_ai_feedback_endpoint_rejects_invalid_rating_value(): void
    {
        $response = $this->postJson(route('api.ai.feedback'), [
            'session_id' => 'sess-feedback-002',
            'message_id' => 'msg-feedback-002',
            'rating' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rating']);
    }

    public function test_ai_feedback_endpoint_assigns_v2_defaults_for_legacy_payload(): void
    {
        $response = $this->postJson(route('api.ai.feedback'), [
            'session_id' => 'sess-feedback-legacy-001',
            'rating' => -1,
            'reason' => 'Jawaban kurang tepat.',
            'metadata' => [
                'provider' => 'deepseek',
                'model' => 'deepseek-chat',
                'fallback_used' => true,
                'status' => 'fallback_success',
            ],
        ]);

        $response->assertCreated();

        $feedback = AiAssistantFeedback::query()->where('session_id', 'sess-feedback-legacy-001')->first();
        $this->assertNotNull($feedback);
        $this->assertSame('not_helpful_generic', $feedback->reason_code);
        $this->assertSame('Jawaban kurang tepat.', $feedback->reason_detail);
        $this->assertSame(2, (int) $feedback->feedback_version);
        $this->assertSame('deepseek', $feedback->provider);
        $this->assertSame('deepseek-chat', $feedback->model);
        $this->assertSame('fallback_success', $feedback->llm_status);
        $this->assertTrue((bool) $feedback->fallback_used);
    }
}
