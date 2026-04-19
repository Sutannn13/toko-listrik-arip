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
            'store_info' => $this->buildFaqResponse($message, 'store_info', $payload),
            'website_help' => $this->buildFaqResponse($message, 'website_help', $payload),
            'troubleshooting' => $this->buildFaqResponse($message, 'troubleshooting', $payload),
            'emotional_support' => $this->buildFaqResponse($message, 'emotional_support', $payload),
            'off_topic' => $this->buildFaqResponse($message, 'off_topic', $payload),
            default => $this->buildFaqResponse($message, 'faq', $payload),
        };

        $response['message_id'] = (string) Str::uuid();
        $response['generated_at'] = now()->toISOString();

        return $response;
    }

    private function buildFaqResponse(string $message, string $resolvedIntent = 'faq', array $payload = []): array
    {
        $faqResult = $this->faqAnswerTool->answer($message);
        $sharedContextData = $this->buildSharedContextData($payload);

        $response = [
            'reply' => $faqResult['answer'],
            'intent' => $resolvedIntent,
            'used_tools' => ['FaqAnswerTool'],
            'suggestions' => $faqResult['suggestions'],
            'data' => array_merge([
                'source_key' => $faqResult['source_key'],
                'confidence' => $faqResult['confidence'],
            ], $sharedContextData),
        ];

        return $this->decorateWithProviderReply($response, $message, $resolvedIntent);
    }

    private function buildOrderTrackingResponse(array $payload, ?User $authenticatedUser): array
    {
        $trackingResult = $this->orderTrackingTool->lookup($payload, $authenticatedUser);
        $message = trim((string) ($payload['message'] ?? ''));
        $sharedContextData = $this->buildSharedContextData($payload);

        $response = [
            'reply' => $trackingResult['reply'],
            'intent' => 'order_tracking',
            'used_tools' => ['OrderTrackingTool'],
            'suggestions' => $trackingResult['suggestions'],
            'data' => array_merge([
                'requires_verification' => $trackingResult['requires_verification'],
                'order' => $trackingResult['order'],
            ], $sharedContextData),
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
        $sharedContextData = $this->buildSharedContextData($payload);

        $response = [
            'reply' => $recommendationResult['reply'],
            'intent' => 'product_recommendation',
            'used_tools' => ['ProductRecommendationTool'],
            'suggestions' => $recommendationResult['suggestions'],
            'data' => array_merge([
                'products' => $recommendationResult['products'],
                'recommendation_meta' => $recommendationMeta,
            ], $sharedContextData),
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

    private function buildSharedContextData(array $payload): array
    {
        $sharedContextData = [];

        $pageContext = $this->extractPageContext($payload);
        if ($pageContext !== []) {
            $sharedContextData['page_context'] = $pageContext;
        }

        $conversationHistory = $this->extractConversationHistory($payload);
        if ($conversationHistory !== []) {
            $sharedContextData['conversation_history'] = $conversationHistory;
        }

        return $sharedContextData;
    }

    private function extractPageContext(array $payload): array
    {
        $context = is_array($payload['context'] ?? null)
            ? $payload['context']
            : [];

        if ($context === []) {
            return [];
        }

        $normalizedContext = [];
        $allowedContextKeys = ['locale', 'channel', 'page_title', 'page_path', 'product_name'];

        foreach ($allowedContextKeys as $contextKey) {
            $contextValue = trim((string) ($context[$contextKey] ?? ''));
            if ($contextValue !== '') {
                $normalizedContext[$contextKey] = $contextValue;
            }
        }

        return $normalizedContext;
    }

    private function extractConversationHistory(array $payload): array
    {
        $history = is_array($payload['history'] ?? null)
            ? $payload['history']
            : [];

        if ($history === []) {
            return [];
        }

        $normalizedHistory = [];

        foreach (array_slice($history, -6) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $role = strtolower(trim((string) ($entry['role'] ?? '')));
            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $text = trim((string) ($entry['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $normalizedHistory[] = [
                'role' => $role,
                'text' => Str::limit($text, 280, '...'),
            ];
        }

        return $normalizedHistory;
    }
}
