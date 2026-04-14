<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AiChatRequest;
use App\Models\User;
use App\Services\Ai\AiAssistantOrchestratorService;
use Illuminate\Http\JsonResponse;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantOrchestratorService $orchestrator,
    ) {}

    public function chat(AiChatRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $authenticatedUser = $request->user();

        $responsePayload = $this->orchestrator->respond(
            $validated,
            $authenticatedUser instanceof User ? $authenticatedUser : null,
        );

        return response()->json($responsePayload);
    }
}
