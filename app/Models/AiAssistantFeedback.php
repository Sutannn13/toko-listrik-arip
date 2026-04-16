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
        'rating',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
