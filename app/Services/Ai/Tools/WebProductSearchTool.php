<?php

namespace App\Services\Ai\Tools;

use Illuminate\Support\Facades\Http;
use Throwable;

class WebProductSearchTool
{
    /**
     * @return array{
     *   status: string,
     *   query: string,
     *   results: array<int, array{title: string, url: string, snippet: string}>
     * }
     */
    public function search(string $query): array
    {
        $normalizedQuery = trim($query);

        if ($normalizedQuery === '') {
            return [
                'status' => 'empty_query',
                'query' => '',
                'results' => [],
            ];
        }

        if (! $this->isEnabled()) {
            return [
                'status' => 'disabled',
                'query' => $normalizedQuery,
                'results' => [],
            ];
        }

        try {
            $response = Http::acceptJson()
                ->timeout($this->requestTimeout())
                ->get($this->resolveEndpoint(), [
                    'q' => $normalizedQuery . ' spesifikasi produk listrik',
                    'format' => 'json',
                    'no_html' => 1,
                    'no_redirect' => 1,
                    'skip_disambig' => 1,
                ]);

            if (! $response->successful()) {
                return [
                    'status' => 'failed_http_' . $response->status(),
                    'query' => $normalizedQuery,
                    'results' => [],
                ];
            }

            $results = $this->extractResults((array) $response->json());

            return [
                'status' => count($results) > 0 ? 'ok' : 'empty',
                'query' => $normalizedQuery,
                'results' => $results,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'status' => 'failed_exception',
                'query' => $normalizedQuery,
                'results' => [],
            ];
        }
    }

    private function resolveEndpoint(): string
    {
        $configuredEndpoint = trim((string) config('services.ai.web_search_endpoint', 'https://api.duckduckgo.com/'));

        if ($configuredEndpoint === '') {
            return 'https://api.duckduckgo.com/';
        }

        return $configuredEndpoint;
    }

    private function requestTimeout(): int
    {
        return max(4, min(20, (int) config('services.ai.web_search_timeout', 8)));
    }

    private function maxResults(): int
    {
        return max(1, min(5, (int) config('services.ai.web_search_max_results', 3)));
    }

    private function isEnabled(): bool
    {
        return filter_var(config('services.ai.web_search_enabled', false), FILTER_VALIDATE_BOOLEAN) !== false;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array{title: string, url: string, snippet: string}>
     */
    private function extractResults(array $payload): array
    {
        $results = [];
        $maxResults = $this->maxResults();

        $primaryAbstract = trim((string) ($payload['AbstractText'] ?? ''));
        $primaryUrl = trim((string) ($payload['AbstractURL'] ?? ''));
        $primaryTitle = trim((string) ($payload['Heading'] ?? ''));

        if ($primaryAbstract !== '' && $primaryUrl !== '' && $this->isValidReferenceUrl($primaryUrl)) {
            $results[] = [
                'title' => $primaryTitle !== '' ? $primaryTitle : 'Referensi produk',
                'url' => $primaryUrl,
                'snippet' => $primaryAbstract,
            ];
        }

        $relatedTopics = $payload['RelatedTopics'] ?? [];
        if (! is_array($relatedTopics)) {
            return array_slice($results, 0, $maxResults);
        }

        foreach ($relatedTopics as $topic) {
            if (count($results) >= $maxResults) {
                break;
            }

            if (is_array($topic) && array_key_exists('Topics', $topic) && is_array($topic['Topics'])) {
                foreach ($topic['Topics'] as $nestedTopic) {
                    if (count($results) >= $maxResults) {
                        break;
                    }

                    $normalized = $this->normalizeTopicResult($nestedTopic);
                    if ($normalized !== null) {
                        $results[] = $normalized;
                    }
                }

                continue;
            }

            $normalized = $this->normalizeTopicResult($topic);
            if ($normalized !== null) {
                $results[] = $normalized;
            }
        }

        return array_slice($results, 0, $maxResults);
    }

    /**
     * @param mixed $topic
     * @return array{title: string, url: string, snippet: string}|null
     */
    private function normalizeTopicResult(mixed $topic): ?array
    {
        if (! is_array($topic)) {
            return null;
        }

        $text = trim((string) ($topic['Text'] ?? ''));
        $url = trim((string) ($topic['FirstURL'] ?? ''));

        if ($text === '' || $url === '' || ! $this->isValidReferenceUrl($url)) {
            return null;
        }

        $parts = explode(' - ', $text, 2);
        $title = trim((string) ($parts[0] ?? ''));
        $snippet = trim((string) ($parts[1] ?? $text));

        if ($title === '') {
            $title = 'Referensi produk';
        }

        if ($snippet === '') {
            $snippet = $text;
        }

        return [
            'title' => $title,
            'url' => $url,
            'snippet' => $snippet,
        ];
    }

    private function isValidReferenceUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
