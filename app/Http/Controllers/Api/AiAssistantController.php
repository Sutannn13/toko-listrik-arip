<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AiChatRequest;
use App\Http\Requests\Api\AiFeedbackStoreRequest;
use App\Models\AiAssistantFeedback;
use App\Models\User;
use App\Services\Ai\AiConversationMemoryService;
use App\Services\Ai\AiAssistantOrchestratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantOrchestratorService $orchestrator,
        private readonly AiConversationMemoryService $conversationMemory,
    ) {}

    public function chat(AiChatRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $authenticatedUser = $request->user();
        $resolvedUser = $authenticatedUser instanceof User ? $authenticatedUser : null;

        $preparedPayload = $this->conversationMemory->preparePayloadForChat(
            $validated,
            $resolvedUser,
        );

        $responsePayload = $this->orchestrator->respond(
            $preparedPayload,
            $resolvedUser,
        );

        $this->conversationMemory->rememberAssistantResponse(
            $preparedPayload,
            $responsePayload,
            $resolvedUser,
        );

        return response()->json($responsePayload);
    }

    public function feedback(AiFeedbackStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $authenticatedUser = $request->user();
        $metadata = is_array($validated['metadata'] ?? null) ? $validated['metadata'] : [];

        $resolvedProvider = trim((string) ($validated['provider'] ?? data_get($metadata, 'provider', '')));
        $resolvedModel = trim((string) ($validated['model'] ?? data_get($metadata, 'model', '')));
        $resolvedLlmStatus = trim((string) ($validated['llm_status'] ?? data_get($metadata, 'status', '')));
        $resolvedFallback = array_key_exists('fallback_used', $validated)
            ? filter_var($validated['fallback_used'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : filter_var(data_get($metadata, 'fallback_used', null), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $normalizedIntent = trim((string) ($validated['intent'] ?? ''));
        $resolvedReasonCode = trim((string) ($validated['reason_code'] ?? ''));

        if ($resolvedReasonCode === '') {
            $resolvedReasonCode = ((int) $validated['rating']) > 0
                ? 'helpful_generic'
                : 'not_helpful_generic';
        }

        $feedbackPayload = [
            'user_id' => $authenticatedUser instanceof User ? $authenticatedUser->id : null,
            'session_id' => $validated['session_id'],
            'message_id' => $validated['message_id'] ?? null,
            'intent' => $normalizedIntent !== '' ? $normalizedIntent : null,
            'intent_detected' => $validated['intent_detected'] ?? ($normalizedIntent !== '' ? $normalizedIntent : null),
            'intent_resolved' => $validated['intent_resolved'] ?? ($normalizedIntent !== '' ? $normalizedIntent : null),
            'rating' => $validated['rating'],
            'reason' => $validated['reason'] ?? null,
            'reason_code' => $resolvedReasonCode,
            'reason_detail' => $validated['reason_detail'] ?? ($validated['reason'] ?? null),
            'provider' => $resolvedProvider !== '' ? $resolvedProvider : null,
            'model' => $resolvedModel !== '' ? $resolvedModel : null,
            'llm_status' => $resolvedLlmStatus !== '' ? $resolvedLlmStatus : null,
            'fallback_used' => $resolvedFallback,
            'response_latency_ms' => $validated['response_latency_ms'] ?? null,
            'prompt_version' => $validated['prompt_version'] ?? null,
            'rule_version' => $validated['rule_version'] ?? null,
            'response_source' => $validated['response_source'] ?? null,
            'feedback_version' => $validated['feedback_version'] ?? 2,
            'metadata' => $metadata !== [] ? $metadata : null,
        ];

        $availableColumns = array_flip(Schema::getColumnListing('ai_assistant_feedback'));
        $feedbackPayload = array_intersect_key($feedbackPayload, $availableColumns);

        $feedback = AiAssistantFeedback::create($feedbackPayload);

        return response()->json([
            'message' => 'Feedback AI berhasil disimpan.',
            'data' => [
                'id' => $feedback->id,
            ],
        ], 201);
    }
}
