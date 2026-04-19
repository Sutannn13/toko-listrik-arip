<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptLearningRule extends Model
{
    protected $fillable = [
        'rule_key',
        'intent',
        'source',
        'trigger_keywords',
        'directive',
        'negative_feedback_count',
        'sample_count',
        'confidence_score',
        'lookback_days',
        'last_learned_at',
        'is_active',
        'metrics',
    ];

    protected $casts = [
        'trigger_keywords' => 'array',
        'metrics' => 'array',
        'is_active' => 'boolean',
        'last_learned_at' => 'datetime',
        'confidence_score' => 'float',
    ];
}
