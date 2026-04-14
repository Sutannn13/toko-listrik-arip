<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Services\Ai\Tools\FaqAnswerTool;
use App\Services\Ai\Tools\OrderTrackingTool;
use App\Services\Ai\Tools\ProductRecommendationTool;

class AiAssistantOrchestratorService
{
    public function __construct(
        private readonly AiIntentRouterService $intentRouter,
        private readonly AiProviderResponderService $providerResponder,
        private readonly FaqAnswerTool $faqAnswerTool,
        private readonly OrderTrackingTool $orderTrackingTool,
        private readonly ProductRecommendationTool $productRecommendationTool,
    ) {}

    public function respond(array $payload, ?User $authenticatedUser): array
    {
        $message = trim((string) ($payload['message'] ?? ''));
        $intent = $this->intentRouter->resolveIntent($message);

        return match ($intent) {
            'order_tracking' => $this->buildOrderTrackingResponse($payload, $authenticatedUser),
            'product_recommendation' => $this->buildRecommendationResponse($payload),
            default => $this->buildFaqResponse($message),
        };
    }

    private function buildFaqResponse(string $message): array
    {
        $faqResult = $this->faqAnswerTool->answer($message);

        $response = [
            'reply' => $faqResult['answer'],
            'intent' => 'faq',
            'used_tools' => ['FaqAnswerTool'],
            'suggestions' => $faqResult['suggestions'],
            'data' => [
                'source_key' => $faqResult['source_key'],
                'confidence' => $faqResult['confidence'],
            ],
        ];

        return $this->decorateWithProviderReply($response, $message);
    }

    private function buildOrderTrackingResponse(array $payload, ?User $authenticatedUser): array
    {
        $trackingResult = $this->orderTrackingTool->lookup($payload, $authenticatedUser);
        $message = trim((string) ($payload['message'] ?? ''));

        $response = [
            'reply' => $trackingResult['reply'],
            'intent' => 'order_tracking',
            'used_tools' => ['OrderTrackingTool'],
            'suggestions' => $trackingResult['suggestions'],
            'data' => [
                'requires_verification' => $trackingResult['requires_verification'],
                'order' => $trackingResult['order'],
            ],
        ];

        return $this->decorateWithProviderReply($response, $message);
    }

    private function buildRecommendationResponse(array $payload): array
    {
        $recommendationResult = $this->productRecommendationTool->recommend($payload);
        $message = trim((string) ($payload['message'] ?? ''));

        $response = [
            'reply' => $recommendationResult['reply'],
            'intent' => 'product_recommendation',
            'used_tools' => ['ProductRecommendationTool'],
            'suggestions' => $recommendationResult['suggestions'],
            'data' => [
                'products' => $recommendationResult['products'],
            ],
        ];

        return $this->decorateWithProviderReply($response, $message);
    }

    private function decorateWithProviderReply(array $baseResponse, string $message): array
    {
        $providerResponse = $this->providerResponder->enhanceReply(
            (string) ($baseResponse['intent'] ?? 'faq'),
            $message,
            (string) ($baseResponse['reply'] ?? ''),
            is_array($baseResponse['suggestions'] ?? null) ? $baseResponse['suggestions'] : [],
        );

        if ($providerResponse === null) {
            return $baseResponse;
        }

        $baseResponse['reply'] = $providerResponse['reply'];
        $baseResponse['data']['llm'] = [
            'provider' => $providerResponse['provider'],
            'model' => $providerResponse['model'],
            'fallback_used' => $providerResponse['fallback_used'],
        ];

        return $baseResponse;
    }
}
