<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAssistantSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'channel',
        'locale',
        'last_page_path',
        'last_page_title',
        'last_intent',
        'last_message_id',
        'turns_count',
        'last_activity_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiAssistantMessage::class);
    }
}
