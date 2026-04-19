<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssistantMessage extends Model
{
    protected $fillable = [
        'ai_assistant_session_id',
        'user_id',
        'role',
        'intent',
        'message_id',
        'content',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAssistantSession::class, 'ai_assistant_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
