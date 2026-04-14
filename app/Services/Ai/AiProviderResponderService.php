<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AiProviderResponderService
{
    public function enhanceReply(string $intent, string $message, string $toolReply, array $suggestions = []): ?array
    {
        if (! $this->isExternalAiEnabled()) {
            return null;
        }

        $primaryProvider = $this->normalizeProvider((string) config('services.ai.provider', 'rule_based'));
        if ($primaryProvider === 'rule_based') {
            return null;
        }

        $primaryModel = $this->resolveFastModel($primaryProvider);
        $prompt = $this->buildPrompt($intent, $message, $toolReply, $suggestions);

        try {
            $reply = $this->requestCompletion($primaryProvider, $primaryModel, $prompt);

            return [
                'reply' => $reply,
                'provider' => $primaryProvider,
                'model' => $primaryModel,
                'fallback_used' => false,
            ];
        } catch (Throwable $exception) {
            report($exception);
        }

        [$fallbackProvider, $fallbackModel] = $this->resolveFallbackTarget($primaryProvider, $primaryModel);

        if ($fallbackProvider === $primaryProvider && $fallbackModel === $primaryModel) {
            return null;
        }

        try {
            $reply = $this->requestCompletion($fallbackProvider, $fallbackModel, $prompt);

            return [
                'reply' => $reply,
                'provider' => $fallbackProvider,
                'model' => $fallbackModel,
                'fallback_used' => true,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function isExternalAiEnabled(): bool
    {
        $assistantEnabled = config('services.ai.assistant_enabled', true);

        return filter_var($assistantEnabled, FILTER_VALIDATE_BOOLEAN) !== false;
    }

    private function resolveFastModel(string $provider): string
    {
        $configuredFastModel = trim((string) config('services.ai.model_fast', ''));

        if ($configuredFastModel !== '') {
            return $configuredFastModel;
        }

        return $this->resolveDefaultModelForProvider($provider);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveFallbackTarget(string $primaryProvider, string $primaryModel): array
    {
        $configuredFallbackModel = trim((string) config('services.ai.model_fallback', ''));

        if ($configuredFallbackModel !== '') {
            $detectedFallbackProvider = $this->detectProviderFromModel($configuredFallbackModel);

            return [
                $detectedFallbackProvider ?? $this->resolveOppositeProvider($primaryProvider),
                $configuredFallbackModel,
            ];
        }

        $fallbackProvider = $this->resolveOppositeProvider($primaryProvider);
        $fallbackModel = $this->resolveDefaultModelForProvider($fallbackProvider);

        if ($fallbackModel === $primaryModel && $fallbackProvider === $primaryProvider) {
            return [$primaryProvider, $primaryModel];
        }

        return [$fallbackProvider, $fallbackModel];
    }

    private function resolveOppositeProvider(string $provider): string
    {
        return $provider === 'gemini' ? 'deepseek' : 'gemini';
    }

    private function detectProviderFromModel(string $model): ?string
    {
        $normalizedModel = strtolower(trim($model));

        if ($normalizedModel === '') {
            return null;
        }

        if (str_contains($normalizedModel, 'gemini')) {
            return 'gemini';
        }

        if (str_contains($normalizedModel, 'deepseek')) {
            return 'deepseek';
        }

        return null;
    }

    private function normalizeProvider(string $provider): string
    {
        $normalizedProvider = strtolower(trim($provider));

        return match ($normalizedProvider) {
            'gemini' => 'gemini',
            'deepseek' => 'deepseek',
            default => 'rule_based',
        };
    }

    private function resolveDefaultModelForProvider(string $provider): string
    {
        return $provider === 'gemini'
            ? 'gemini-2.5-flash'
            : 'deepseek-chat';
    }

    private function requestCompletion(string $provider, string $model, string $prompt): string
    {
        return match ($provider) {
            'gemini' => $this->requestGeminiCompletion($model, $prompt),
            'deepseek' => $this->requestDeepSeekCompletion($model, $prompt),
            default => throw new RuntimeException('Provider AI tidak didukung: ' . $provider),
        };
    }

    private function requestGeminiCompletion(string $model, string $prompt): string
    {
        $apiKey = trim((string) config('services.ai.gemini_api_key', ''));

        if ($apiKey === '') {
            throw new RuntimeException('AI_GEMINI_API_KEY belum diisi.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout($this->requestTimeout())
            ->withQueryParameters(['key' => $apiKey])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent', [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => $this->maxOutputTokens(),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini request gagal dengan status HTTP ' . $response->status() . '.');
        }

        $reply = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

        if ($reply === '') {
            throw new RuntimeException('Gemini tidak mengembalikan teks jawaban.');
        }

        return $reply;
    }

    private function requestDeepSeekCompletion(string $model, string $prompt): string
    {
        $apiKey = trim((string) config('services.ai.deepseek_api_key', ''));

        if ($apiKey === '') {
            throw new RuntimeException('AI_DEEPSEEK_API_KEY belum diisi.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout($this->requestTimeout())
            ->withToken($apiKey)
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah asisten toko listrik. Jawab ringkas, aman, dan faktual.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
                'max_tokens' => $this->maxOutputTokens(),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('DeepSeek request gagal dengan status HTTP ' . $response->status() . '.');
        }

        $reply = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

        if ($reply === '') {
            throw new RuntimeException('DeepSeek tidak mengembalikan teks jawaban.');
        }

        return $reply;
    }

    private function buildPrompt(string $intent, string $message, string $toolReply, array $suggestions): string
    {
        $suggestionText = '-';

        if (count($suggestions) > 0) {
            $normalizedSuggestions = array_map(
                static fn($suggestion): string => trim((string) $suggestion),
                $suggestions,
            );

            $suggestionText = implode('; ', array_filter($normalizedSuggestions, static fn(string $value): bool => $value !== ''));
            if ($suggestionText === '') {
                $suggestionText = '-';
            }
        }

        return implode("\n", [
            'Jawab sebagai asisten toko listrik dalam Bahasa Indonesia.',
            'Gunakan jawaban dasar sebagai sumber fakta utama, jangan menambah data baru.',
            'Gunakan maksimal 2 kalimat dan tetap jelas untuk pengguna umum.',
            'Intent: ' . $intent,
            'Pertanyaan pelanggan: ' . trim($message),
            'Jawaban dasar: ' . trim($toolReply),
            'Saran tindak lanjut: ' . $suggestionText,
        ]);
    }

    private function requestTimeout(): int
    {
        return max(5, (int) config('services.ai.request_timeout', 20));
    }

    private function maxOutputTokens(): int
    {
        return max(64, min(2048, (int) config('services.ai.max_output_tokens', 500)));
    }
}
