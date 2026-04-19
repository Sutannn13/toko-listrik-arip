<?php

namespace App\Services\Ai;

use App\Models\AiAssistantMessage;
use App\Models\AiAssistantSession;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiConversationMemoryService
{
    private const HISTORY_LIMIT = 8;

    private const FRONTEND_BOOTSTRAP_LIMIT = 6;

    private ?bool $isMemorySchemaReady = null;

    public function preparePayloadForChat(array $validated, ?User $authenticatedUser): array
    {
        $sessionId = trim((string) ($validated['session_id'] ?? ''));

        if ($sessionId === '') {
            return $validated;
        }

        if (! $this->memorySchemaReady()) {
            return $validated;
        }

        try {
            $context = is_array($validated['context'] ?? null) ? $validated['context'] : [];
            $session = $this->upsertSession($sessionId, $authenticatedUser, $context);

            $frontendHistory = is_array($validated['history'] ?? null) ? $validated['history'] : [];
            $this->bootstrapHistoryFromFrontendIfNeeded($session, $frontendHistory, $authenticatedUser);

            $this->storeUserMessage(
                $session,
                $authenticatedUser,
                trim((string) ($validated['message'] ?? '')),
                $context,
            );

            $validated['history'] = $this->loadRecentHistory($session, self::HISTORY_LIMIT);

            return $validated;
        } catch (Throwable $exception) {
            report($exception);

            return $validated;
        }
    }

    public function rememberAssistantResponse(array $payload, array $response, ?User $authenticatedUser): void
    {
        $sessionId = trim((string) ($payload['session_id'] ?? ''));

        if ($sessionId === '') {
            return;
        }

        if (! $this->memorySchemaReady()) {
            return;
        }

        try {
            $session = AiAssistantSession::query()
                ->where('session_id', $sessionId)
                ->first();

            if (! $session) {
                return;
            }

            $reply = trim((string) ($response['reply'] ?? ''));
            if ($reply !== '') {
                AiAssistantMessage::query()->create([
                    'ai_assistant_session_id' => $session->id,
                    'user_id' => $authenticatedUser?->id,
                    'role' => 'assistant',
                    'intent' => trim((string) ($response['intent'] ?? '')) ?: null,
                    'message_id' => trim((string) ($response['message_id'] ?? '')) ?: null,
                    'content' => $reply,
                    'context' => [
                        'suggestions' => array_values(array_slice((array) ($response['suggestions'] ?? []), 0, 5)),
                        'used_tools' => array_values((array) ($response['used_tools'] ?? [])),
                    ],
                ]);
            }

            $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];

            $session->last_intent = trim((string) ($response['intent'] ?? '')) ?: $session->last_intent;
            $session->last_message_id = trim((string) ($response['message_id'] ?? '')) ?: $session->last_message_id;
            $session->last_activity_at = now();
            $session->turns_count = (int) $session->turns_count + 1;
            $session->metadata = array_merge((array) ($session->metadata ?? []), [
                'last_reply_generated_at' => $response['generated_at'] ?? null,
                'last_llm_status' => data_get($responseData, 'llm.status'),
            ]);
            $session->save();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function upsertSession(string $sessionId, ?User $authenticatedUser, array $context): AiAssistantSession
    {
        $session = AiAssistantSession::query()->firstOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $authenticatedUser?->id,
                'turns_count' => 0,
                'last_activity_at' => now(),
            ],
        );

        if ($session->user_id === null && $authenticatedUser) {
            $session->user_id = $authenticatedUser->id;
        }

        $session->channel = $this->normalizeNullableString($context['channel'] ?? null, 50) ?? $session->channel;
        $session->locale = $this->normalizeNullableString($context['locale'] ?? null, 10) ?? $session->locale;
        $session->last_page_path = $this->normalizeNullableString($context['page_path'] ?? null, 255) ?? $session->last_page_path;
        $session->last_page_title = $this->normalizeNullableString($context['page_title'] ?? null, 180) ?? $session->last_page_title;
        $session->last_activity_at = now();
        $session->metadata = array_merge((array) ($session->metadata ?? []), [
            'last_seen_from' => 'api_ai_chat',
        ]);
        $session->save();

        return $session;
    }

    private function bootstrapHistoryFromFrontendIfNeeded(AiAssistantSession $session, array $frontendHistory, ?User $authenticatedUser): void
    {
        if (count($frontendHistory) === 0) {
            return;
        }

        if ($session->messages()->exists()) {
            return;
        }

        $historyToImport = array_slice($frontendHistory, -self::FRONTEND_BOOTSTRAP_LIMIT);

        foreach ($historyToImport as $historyItem) {
            if (! is_array($historyItem)) {
                continue;
            }

            $role = strtolower(trim((string) ($historyItem['role'] ?? '')));
            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $text = trim((string) ($historyItem['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            AiAssistantMessage::query()->create([
                'ai_assistant_session_id' => $session->id,
                'user_id' => $authenticatedUser?->id,
                'role' => $role,
                'content' => Str::limit($text, 500, '...'),
            ]);
        }
    }

    private function storeUserMessage(AiAssistantSession $session, ?User $authenticatedUser, string $message, array $context): void
    {
        if ($message === '') {
            return;
        }

        $latestMessage = $session->messages()->latest('id')->first();
        if (
            $latestMessage
            && $latestMessage->role === 'user'
            && trim((string) $latestMessage->content) === $message
            && $latestMessage->created_at !== null
            && $latestMessage->created_at->greaterThan(now()->subSeconds(10))
        ) {
            return;
        }

        AiAssistantMessage::query()->create([
            'ai_assistant_session_id' => $session->id,
            'user_id' => $authenticatedUser?->id,
            'role' => 'user',
            'content' => Str::limit($message, 2000, '...'),
            'context' => $this->sanitizeContextForMessage($context),
        ]);
    }

    private function loadRecentHistory(AiAssistantSession $session, int $limit): array
    {
        $messages = $session->messages()
            ->latest('id')
            ->limit(max(1, $limit))
            ->get(['role', 'content'])
            ->reverse()
            ->values();

        return $messages
            ->map(static fn(AiAssistantMessage $message): array => [
                'role' => $message->role,
                'text' => Str::limit(trim((string) $message->content), 280, '...'),
            ])
            ->filter(static fn(array $historyItem): bool => $historyItem['text'] !== '')
            ->values()
            ->all();
    }

    private function sanitizeContextForMessage(array $context): array
    {
        $allowedContextKeys = ['locale', 'channel', 'page_title', 'page_path', 'product_name'];
        $sanitizedContext = [];

        foreach ($allowedContextKeys as $contextKey) {
            $normalizedValue = $this->normalizeNullableString($context[$contextKey] ?? null, 255);
            if ($normalizedValue !== null) {
                $sanitizedContext[$contextKey] = $normalizedValue;
            }
        }

        return $sanitizedContext;
    }

    private function normalizeNullableString(mixed $value, int $maxLength): ?string
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            return null;
        }

        return Str::limit($normalizedValue, $maxLength, '');
    }

    private function memorySchemaReady(): bool
    {
        if ($this->isMemorySchemaReady !== null) {
            return $this->isMemorySchemaReady;
        }

        $this->isMemorySchemaReady = Schema::hasTable('ai_assistant_sessions')
            && Schema::hasTable('ai_assistant_messages');

        return $this->isMemorySchemaReady;
    }
}
