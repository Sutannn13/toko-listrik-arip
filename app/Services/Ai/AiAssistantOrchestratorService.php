<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Services\Ai\Tools\FaqAnswerTool;
use App\Services\Ai\Tools\OrderTrackingTool;
use App\Services\Ai\Tools\ProductRecommendationTool;
use Illuminate\Support\Str;

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

        $response = match ($intent) {
            'order_tracking' => $this->buildOrderTrackingResponse($payload, $authenticatedUser),
            'product_recommendation' => $this->buildRecommendationResponse($payload),
            'store_info' => $this->buildFaqResponse($message, 'store_info'),
            'website_help' => $this->buildFaqResponse($message, 'website_help'),
            'troubleshooting' => $this->buildFaqResponse($message, 'troubleshooting'),
            'emotional_support' => $this->buildFaqResponse($message, 'emotional_support'),
            'off_topic' => $this->buildFaqResponse($message, 'off_topic'),
            default => $this->buildFaqResponse($message, 'faq'),
        };

        $response['message_id'] = (string) Str::uuid();
        $response['generated_at'] = now()->toISOString();

        return $response;
    }

    private function buildFaqResponse(string $message, string $resolvedIntent = 'faq'): array
    {
        $faqResult = $this->faqAnswerTool->answer($message);

        $response = [
            'reply' => $faqResult['answer'],
            'intent' => $resolvedIntent,
            'used_tools' => ['FaqAnswerTool'],
            'suggestions' => $faqResult['suggestions'],
            'data' => [
                'source_key' => $faqResult['source_key'],
                'confidence' => $faqResult['confidence'],
            ],
        ];

        return $this->decorateWithProviderReply($response, $message, $resolvedIntent);
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

        return $this->decorateWithProviderReply($response, $message, 'order_tracking');
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

        return $this->decorateWithProviderReply($response, $message, 'product_recommendation');
    }

    private function decorateWithProviderReply(array $baseResponse, string $message, string $intent = 'faq'): array
    {
        $providerResponse = $this->providerResponder->enhanceReply(
            $intent,
            $message,
            (string) ($baseResponse['reply'] ?? ''),
            is_array($baseResponse['suggestions'] ?? null) ? $baseResponse['suggestions'] : [],
            (array) ($baseResponse['data'] ?? [])
        );

        if ($providerResponse === null) {
            return $baseResponse;
        }

        $providerReply = trim((string) ($providerResponse['reply'] ?? ''));

        if ($providerReply !== '') {
            $baseResponse['reply'] = $providerReply;
        }

        $baseResponse['data']['llm'] = [
            'provider' => $providerResponse['provider'],
            'model' => $providerResponse['model'],
            'status' => $providerResponse['status'],
            'fallback_used' => $providerResponse['fallback_used'],
            'attempts' => $providerResponse['attempts'],
        ];

        return $baseResponse;
    }
}
