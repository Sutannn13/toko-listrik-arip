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
            'rating' => 1,
            'reason' => 'Jawabannya membantu.',
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
        $this->assertSame(1, (int) $feedback->rating);
        $this->assertSame('Jawabannya membantu.', $feedback->reason);
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
}
