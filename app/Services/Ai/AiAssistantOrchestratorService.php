<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Services\Ai\Tools\FaqAnswerTool;
use App\Services\Ai\Tools\OrderTrackingTool;
use App\Services\Ai\Tools\ProductRecommendationTool;
use App\Services\Ai\Tools\WebProductSearchTool;
use Illuminate\Support\Str;

class AiAssistantOrchestratorService
{
    public function __construct(
        private readonly AiIntentRouterService $intentRouter,
        private readonly AiProviderResponderService $providerResponder,
        private readonly FaqAnswerTool $faqAnswerTool,
        private readonly OrderTrackingTool $orderTrackingTool,
        private readonly ProductRecommendationTool $productRecommendationTool,
        private readonly WebProductSearchTool $webProductSearchTool,
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
        $recommendationMeta = is_array($recommendationResult['meta'] ?? null)
            ? $recommendationResult['meta']
            : [];

        $response = [
            'reply' => $recommendationResult['reply'],
            'intent' => 'product_recommendation',
            'used_tools' => ['ProductRecommendationTool'],
            'suggestions' => $recommendationResult['suggestions'],
            'data' => [
                'products' => $recommendationResult['products'],
                'recommendation_meta' => $recommendationMeta,
            ],
        ];

        $webSearchResult = $this->maybeSearchWebProductContext($message, $recommendationResult);

        if ($webSearchResult !== null) {
            $response['data']['web_search'] = $webSearchResult;

            if (($webSearchResult['status'] ?? '') === 'ok' && count($webSearchResult['results'] ?? []) > 0) {
                $response['reply'] = $this->appendWebSearchSummary($response['reply'], $webSearchResult);

                $response['used_tools'][] = 'WebProductSearchTool';
                $response['used_tools'] = array_values(array_unique($response['used_tools']));

                $response['suggestions'] = array_values(array_unique(array_merge(
                    is_array($response['suggestions']) ? $response['suggestions'] : [],
                    [
                        'Bandingkan hasil web dengan katalog toko',
                        'Mau saya carikan alternatif produk serupa?',
                    ],
                )));
            }
        }

        return $this->decorateWithProviderReply($response, $message, 'product_recommendation');
    }

    private function maybeSearchWebProductContext(string $message, array $recommendationResult): ?array
    {
        if (! $this->shouldUseWebSearch($message, $recommendationResult)) {
            return null;
        }

        return $this->webProductSearchTool->search($message);
    }

    private function shouldUseWebSearch(string $message, array $recommendationResult): bool
    {
        if (filter_var(config('services.ai.web_search_enabled', false), FILTER_VALIDATE_BOOLEAN) === false) {
            return false;
        }

        $normalizedMessage = strtolower(trim($message));

        if ($normalizedMessage === '') {
            return false;
        }

        $products = is_array($recommendationResult['products'] ?? null)
            ? $recommendationResult['products']
            : [];

        $meta = is_array($recommendationResult['meta'] ?? null)
            ? $recommendationResult['meta']
            : [];

        $matchStrategy = (string) ($meta['match_strategy'] ?? 'none');
        $isDescriptionDriven = (bool) ($meta['description_driven'] ?? false);

        $explicitSearchRequest = preg_match('/\b(search engine|search google|cari di google|cari di internet|search produk|search product|cek internet|hasil pencarian)\b/i', $normalizedMessage) === 1;
        $mentionsProductAlias = preg_match('/\b(?:produk|product)\s+[a-z0-9]{1,10}\b/i', $normalizedMessage) === 1;
        $isCatalogConfidenceLow = count($products) === 0 || $matchStrategy !== 'ranked';

        if ($explicitSearchRequest) {
            return true;
        }

        if ($isCatalogConfidenceLow && ($mentionsProductAlias || $isDescriptionDriven)) {
            return true;
        }

        return false;
    }

    private function appendWebSearchSummary(string $baseReply, array $webSearchResult): string
    {
        $results = is_array($webSearchResult['results'] ?? null)
            ? $webSearchResult['results']
            : [];

        if (count($results) === 0) {
            return $baseReply;
        }

        $summaryLines = ['Referensi tambahan dari search engine:'];

        foreach (array_slice($results, 0, 3) as $index => $result) {
            $title = trim((string) ($result['title'] ?? 'Referensi produk'));
            $snippet = trim((string) ($result['snippet'] ?? ''));
            $url = trim((string) ($result['url'] ?? ''));

            $line = ($index + 1) . '. ' . $title;
            if ($snippet !== '') {
                $line .= ' - ' . Str::limit($snippet, 140, '...');
            }

            $summaryLines[] = $line;

            if ($url !== '') {
                $summaryLines[] = '   Sumber: ' . $url;
            }
        }

        $replySegments = [trim($baseReply), implode("\n", $summaryLines)];

        return trim(implode("\n\n", array_filter($replySegments, static fn(string $segment): bool => $segment !== '')));
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
