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

        $feedback = AiAssistantFeedback::create([
            'user_id' => $authenticatedUser instanceof User ? $authenticatedUser->id : null,
            'session_id' => $validated['session_id'],
            'message_id' => $validated['message_id'] ?? null,
            'intent' => $validated['intent'] ?? null,
            'rating' => $validated['rating'],
            'reason' => $validated['reason'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'message' => 'Feedback AI berhasil disimpan.',
            'data' => [
                'id' => $feedback->id,
            ],
        ], 201);
    }
}
