<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAssistantFeedback extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'message_id',
        'intent',
        'intent_detected',
        'intent_resolved',
        'rating',
        'reason',
        'reason_code',
        'reason_detail',
        'provider',
        'model',
        'llm_status',
        'fallback_used',
        'response_latency_ms',
        'prompt_version',
        'rule_version',
        'response_source',
        'feedback_version',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'fallback_used' => 'boolean',
    ];
}
