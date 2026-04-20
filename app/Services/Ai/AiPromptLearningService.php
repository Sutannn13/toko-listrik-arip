<?php

namespace App\Services\Ai;

use App\Models\AiAssistantFeedback;
use App\Models\AiPromptLearningRule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiPromptLearningService
{
    private const CACHE_TTL_SECONDS = 300;

    private const DEFAULT_ADAPTIVE_RULE_CONTEXT = '- Belum ada rule adaptif aktif dari feedback negatif terbaru.';

    /**
     * @return array<string, mixed>
     */
    public function rebuildRulesFromNegativeFeedback(int $lookbackDays = 30, int $minimumSignalCount = 3): array
    {
        $boundedLookbackDays = max(7, min(120, $lookbackDays));
        $boundedMinimumSignalCount = max(1, min(20, $minimumSignalCount));

        if (! Schema::hasTable('ai_prompt_learning_rules') || ! Schema::hasTable('ai_assistant_feedback')) {
            return [
                'window_days' => $boundedLookbackDays,
                'minimum_signal_count' => $boundedMinimumSignalCount,
                'feedback_samples' => 0,
                'updated_rules' => 0,
                'active_rule_keys' => [],
                'intent_insights' => [],
                'global_rule_active' => false,
            ];
        }

        $startAt = now()->subDays($boundedLookbackDays);

        $negativeFeedbackRows = AiAssistantFeedback::query()
            ->where('rating', -1)
            ->where('created_at', '>=', $startAt)
            ->get([
                'intent',
                'reason',
                'reason_code',
                'llm_status',
                'fallback_used',
                'response_latency_ms',
                'provider',
                'model',
                'created_at',
            ]);

        $groupedByIntent = $negativeFeedbackRows->groupBy(function (AiAssistantFeedback $feedback): string {
            return $this->normalizeIntent((string) $feedback->intent);
        });

        $activeRuleKeys = [];
        $updatedRuleCount = 0;
        $intentInsights = [];

        foreach ($groupedByIntent as $intent => $feedbackGroup) {
            $signalCount = $feedbackGroup->count();

            if ($signalCount < $boundedMinimumSignalCount) {
                continue;
            }

            $normalizedReasons = $feedbackGroup
                ->pluck('reason')
                ->map(fn($reason): string => $this->normalizeReason((string) $reason))
                ->filter(fn(string $reason): bool => $reason !== '')
                ->values()
                ->all();

            $topKeywords = $this->extractTopKeywords($normalizedReasons, 6);

            $normalizedReasonCodes = $feedbackGroup
                ->map(fn(AiAssistantFeedback $feedback): string => $this->normalizeReasonCode((string) $feedback->reason_code, (string) $feedback->reason))
                ->filter(fn(string $reasonCode): bool => $reasonCode !== '')
                ->values()
                ->all();

            $topReasonCodes = $this->extractTopReasonCodes($normalizedReasonCodes, 6);

            if (count($topKeywords) === 0 && count($topReasonCodes) === 0) {
                continue;
            }

            $qualitySignals = $this->collectQualitySignals($feedbackGroup);
            $confidenceScore = $this->calculateConfidenceScore($signalCount, $topReasonCodes, $qualitySignals);
            $ruleKey = 'negative_feedback:' . $intent;

            AiPromptLearningRule::query()->updateOrCreate(
                ['rule_key' => $ruleKey],
                [
                    'intent' => $intent,
                    'source' => 'negative_feedback',
                    'trigger_keywords' => $topKeywords,
                    'directive' => $this->buildDirectiveForIntent($intent, $topKeywords, $topReasonCodes, $qualitySignals),
                    'negative_feedback_count' => $signalCount,
                    'sample_count' => $signalCount,
                    'confidence_score' => $confidenceScore,
                    'lookback_days' => $boundedLookbackDays,
                    'last_learned_at' => now(),
                    'is_active' => true,
                    'metrics' => [
                        'top_keywords' => $topKeywords,
                        'top_reason_codes' => $topReasonCodes,
                        'examples' => array_values(array_slice($normalizedReasons, 0, 3)),
                        'quality_signals' => $qualitySignals,
                        'providers' => $this->extractTopProviders($feedbackGroup),
                    ],
                ],
            );

            $activeRuleKeys[] = $ruleKey;
            $updatedRuleCount++;

            $intentInsights[] = [
                'intent' => $intent,
                'signal_count' => $signalCount,
                'top_reason_codes' => $topReasonCodes,
                'quality_signals' => $qualitySignals,
            ];
        }

        $globalRuleActive = false;
        $globalMinimumSignalCount = max(4, $boundedMinimumSignalCount * 2);

        if ($negativeFeedbackRows->count() >= $globalMinimumSignalCount) {
            $globalReasons = $negativeFeedbackRows
                ->pluck('reason')
                ->map(fn($reason): string => $this->normalizeReason((string) $reason))
                ->filter(fn(string $reason): bool => $reason !== '')
                ->values()
                ->all();

            $globalTopKeywords = $this->extractTopKeywords($globalReasons, 8);

            $globalReasonCodes = $negativeFeedbackRows
                ->map(fn(AiAssistantFeedback $feedback): string => $this->normalizeReasonCode((string) $feedback->reason_code, (string) $feedback->reason))
                ->filter(fn(string $reasonCode): bool => $reasonCode !== '')
                ->values()
                ->all();

            $globalTopReasonCodes = $this->extractTopReasonCodes($globalReasonCodes, 8);

            if (count($globalTopKeywords) > 0 || count($globalTopReasonCodes) > 0) {
                $globalQualitySignals = $this->collectQualitySignals($negativeFeedbackRows);
                $globalRuleKey = 'negative_feedback:global';

                AiPromptLearningRule::query()->updateOrCreate(
                    ['rule_key' => $globalRuleKey],
                    [
                        'intent' => 'global',
                        'source' => 'negative_feedback',
                        'trigger_keywords' => $globalTopKeywords,
                        'directive' => $this->buildDirectiveForIntent('global', $globalTopKeywords, $globalTopReasonCodes, $globalQualitySignals),
                        'negative_feedback_count' => $negativeFeedbackRows->count(),
                        'sample_count' => $negativeFeedbackRows->count(),
                        'confidence_score' => $this->calculateConfidenceScore($negativeFeedbackRows->count(), $globalTopReasonCodes, $globalQualitySignals),
                        'lookback_days' => $boundedLookbackDays,
                        'last_learned_at' => now(),
                        'is_active' => true,
                        'metrics' => [
                            'top_keywords' => $globalTopKeywords,
                            'top_reason_codes' => $globalTopReasonCodes,
                            'examples' => array_values(array_slice($globalReasons, 0, 3)),
                            'quality_signals' => $globalQualitySignals,
                            'providers' => $this->extractTopProviders($negativeFeedbackRows),
                        ],
                    ],
                );

                $activeRuleKeys[] = $globalRuleKey;
                $updatedRuleCount++;
                $globalRuleActive = true;
            }
        }

        AiPromptLearningRule::query()
            ->where('source', 'negative_feedback')
            ->when(count($activeRuleKeys) > 0, function ($query) use ($activeRuleKeys) {
                $query->whereNotIn('rule_key', $activeRuleKeys);
            })
            ->when(count($activeRuleKeys) === 0, function ($query) {
                return $query;
            })
            ->update(['is_active' => false]);

        Cache::forget('ai_prompt_learning_context');

        return [
            'window_days' => $boundedLookbackDays,
            'minimum_signal_count' => $boundedMinimumSignalCount,
            'feedback_samples' => $negativeFeedbackRows->count(),
            'updated_rules' => $updatedRuleCount,
            'active_rule_keys' => $activeRuleKeys,
            'intent_insights' => $intentInsights,
            'global_rule_active' => $globalRuleActive,
        ];
    }

    public function buildPromptAdjustmentContext(): string
    {
        if (! Schema::hasTable('ai_prompt_learning_rules')) {
            return self::DEFAULT_ADAPTIVE_RULE_CONTEXT;
        }

        return Cache::remember('ai_prompt_learning_context', self::CACHE_TTL_SECONDS, function (): string {
            try {
                return $this->compilePromptAdjustmentContext();
            } catch (Throwable $exception) {
                report($exception);

                return self::DEFAULT_ADAPTIVE_RULE_CONTEXT;
            }
        });
    }

    public function currentRuleVersion(): string
    {
        if (! Schema::hasTable('ai_prompt_learning_rules')) {
            return 'rules:none';
        }

        try {
            $activeRulesQuery = AiPromptLearningRule::query()
                ->where('is_active', true)
                ->where('source', 'negative_feedback');

            $activeRuleCount = (clone $activeRulesQuery)->count();

            if ($activeRuleCount === 0) {
                return 'rules:none';
            }

            $latestUpdatedAt = (clone $activeRulesQuery)
                ->max('updated_at');

            if (! is_string($latestUpdatedAt) || trim($latestUpdatedAt) === '') {
                return 'rules:' . $activeRuleCount;
            }

            $normalizedTimestamp = Carbon::parse($latestUpdatedAt)->format('YmdHis');

            return 'rules:' . $activeRuleCount . ':' . $normalizedTimestamp;
        } catch (Throwable $exception) {
            report($exception);

            return 'rules:unknown';
        }
    }

    private function compilePromptAdjustmentContext(): string
    {
        $activeRules = AiPromptLearningRule::query()
            ->where('is_active', true)
            ->where('source', 'negative_feedback')
            ->orderByDesc('confidence_score')
            ->orderByDesc('negative_feedback_count')
            ->limit(10)
            ->get();

        if ($activeRules->isEmpty()) {
            return self::DEFAULT_ADAPTIVE_RULE_CONTEXT;
        }

        $lines = [];

        foreach ($activeRules as $activeRule) {
            $intentLabel = (string) ($activeRule->intent ?: 'faq');
            $negativeFeedbackCount = (int) $activeRule->negative_feedback_count;
            $confidenceScore = number_format((float) $activeRule->confidence_score, 2);
            $keywords = is_array($activeRule->trigger_keywords) ? $activeRule->trigger_keywords : [];
            $keywordLabel = count($keywords) > 0 ? implode(', ', array_slice($keywords, 0, 4)) : '-';

            $topReasonCodes = data_get($activeRule->metrics, 'top_reason_codes', []);
            $reasonCodeLabel = is_array($topReasonCodes) && count($topReasonCodes) > 0
                ? implode(', ', array_slice($topReasonCodes, 0, 3))
                : '-';

            $llmFailureRate = (float) data_get($activeRule->metrics, 'quality_signals.llm_failure_rate_percent', 0.0);
            $highLatencyRate = (float) data_get($activeRule->metrics, 'quality_signals.high_latency_rate_percent', 0.0);

            $runtimeLabel = 'LLM failure: ' . number_format($llmFailureRate, 1) . '%; high latency: ' . number_format($highLatencyRate, 1) . '%';

            $lines[] = '- Intent ' . $intentLabel
                . ' | sinyal negatif: ' . $negativeFeedbackCount
                . ' | confidence: ' . $confidenceScore
                . ' | keyword: ' . $keywordLabel
                . ' | reason_code: ' . $reasonCodeLabel
                . ' | runtime: ' . $runtimeLabel
                . ' | aturan: ' . trim((string) $activeRule->directive);
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, string> $topKeywords
     * @param array<int, string> $topReasonCodes
     * @param array<string, mixed> $qualitySignals
     */
    private function buildDirectiveForIntent(string $intent, array $topKeywords, array $topReasonCodes, array $qualitySignals): string
    {
        $keywordHint = count($topKeywords) > 0
            ? 'Perhatikan kata kunci masalah: ' . implode(', ', array_slice($topKeywords, 0, 5)) . '.'
            : 'Perhatikan pola keluhan terbaru pengguna.';

        $reasonCodeHint = count($topReasonCodes) > 0
            ? 'Prioritaskan reason_code ini: ' . implode(', ', array_slice($topReasonCodes, 0, 5)) . '.'
            : 'Jika reason_code kosong, gunakan konteks percakapan untuk diagnosis detail.';

        $runtimeHints = [];

        if ((float) ($qualitySignals['llm_failure_rate_percent'] ?? 0.0) >= 25) {
            $runtimeHints[] = 'Kurangi ketergantungan pada jawaban generik; pastikan fallback jawaban tetap actionable meski provider bermasalah.';
        }

        if ((float) ($qualitySignals['high_latency_rate_percent'] ?? 0.0) >= 20) {
            $runtimeHints[] = 'Utamakan struktur jawaban ringkas dan prioritas inti agar respons tetap cepat.';
        }

        $runtimeHint = count($runtimeHints) > 0
            ? implode(' ', $runtimeHints)
            : 'Pertahankan jawaban tetap singkat, jelas, dan langsung mengarah ke next action.';

        return match ($intent) {
            'website_help' => 'Untuk website_help, jawab step-by-step sesuai halaman aktif, hindari jawaban umum tanpa langkah operasional. ' . $keywordHint . ' ' . $reasonCodeHint . ' ' . $runtimeHint,
            'order_tracking' => 'Untuk order_tracking, selalu verifikasi identitas user lalu berikan status terbaru, next action, dan jalur eskalasi jika terjadi kendala. ' . $keywordHint . ' ' . $reasonCodeHint . ' ' . $runtimeHint,
            'product_recommendation' => 'Untuk product_recommendation, kurangi jawaban generik; pilih produk berdasarkan budget, konteks ruangan, dan jelaskan alasan pemilihan produk. ' . $keywordHint . ' ' . $reasonCodeHint . ' ' . $runtimeHint,
            'troubleshooting' => 'Untuk troubleshooting, mulai dari empati singkat lalu diagnosis 1-3 langkah mandiri sebelum eskalasi ke admin. ' . $keywordHint . ' ' . $reasonCodeHint . ' ' . $runtimeHint,
            'global' => 'Untuk semua intent, prioritaskan akurasi, next action yang bisa dijalankan user, dan konsistensi dengan kebijakan toko. ' . $keywordHint . ' ' . $reasonCodeHint . ' ' . $runtimeHint,
            default => 'Perjelas jawaban sesuai konteks user, ringkas namun actionable, dan hindari asumsi tanpa data. ' . $keywordHint . ' ' . $reasonCodeHint . ' ' . $runtimeHint,
        };
    }

    private function normalizeIntent(string $intent): string
    {
        $normalizedIntent = strtolower(trim($intent));

        return $normalizedIntent !== '' ? $normalizedIntent : 'faq';
    }

    private function normalizeReason(string $reason): string
    {
        $strippedReason = strip_tags($reason);
        $normalizedReason = preg_replace('/\s+/', ' ', $strippedReason);

        return trim((string) $normalizedReason);
    }

    private function normalizeReasonCode(string $reasonCode, string $fallbackReason = ''): string
    {
        $normalizedReasonCode = strtolower(trim($reasonCode));
        $normalizedReasonCode = str_replace(['-', ' '], '_', $normalizedReasonCode);
        $normalizedReasonCode = preg_replace('/[^a-z0-9_]/', '_', $normalizedReasonCode) ?: '';
        $normalizedReasonCode = preg_replace('/_+/', '_', $normalizedReasonCode) ?: '';
        $normalizedReasonCode = trim($normalizedReasonCode, '_');

        if ($normalizedReasonCode !== '') {
            return $normalizedReasonCode;
        }

        $normalizedFallbackReason = strtolower($this->normalizeReason($fallbackReason));

        if ($normalizedFallbackReason === '') {
            return 'not_helpful_generic';
        }

        if (str_contains($normalizedFallbackReason, 'lambat') || str_contains($normalizedFallbackReason, 'lemot') || str_contains($normalizedFallbackReason, 'lama')) {
            return 'not_helpful_slow_response';
        }

        if (str_contains($normalizedFallbackReason, 'ngaco') || str_contains($normalizedFallbackReason, 'salah') || str_contains($normalizedFallbackReason, 'tidak akurat')) {
            return 'not_helpful_hallucination';
        }

        if (str_contains($normalizedFallbackReason, 'intent') || str_contains($normalizedFallbackReason, 'meleset')) {
            return 'not_helpful_wrong_intent';
        }

        if (str_contains($normalizedFallbackReason, 'kurang jelas') || str_contains($normalizedFallbackReason, 'membingungkan') || str_contains($normalizedFallbackReason, 'ga jelas')) {
            return 'not_helpful_incomplete_answer';
        }

        return 'not_helpful_generic';
    }

    /**
     * @return array<string, mixed>
     */
    private function collectQualitySignals(Collection $feedbackRows): array
    {
        $totalCount = max(1, $feedbackRows->count());

        $failureStatuses = [
            'primary_failed',
            'fallback_failed',
            'prompt_build_failed',
            'fallback_unavailable',
            'budget_exhausted',
            'fallback_budget_exhausted',
        ];

        $llmFailureCount = $feedbackRows
            ->filter(function (AiAssistantFeedback $feedback) use ($failureStatuses): bool {
                $status = strtolower(trim((string) $feedback->llm_status));

                return in_array($status, $failureStatuses, true);
            })
            ->count();

        $fallbackCount = $feedbackRows
            ->filter(fn(AiAssistantFeedback $feedback): bool => (bool) $feedback->fallback_used)
            ->count();

        $latencyValues = $feedbackRows
            ->pluck('response_latency_ms')
            ->filter(fn($latency): bool => is_numeric($latency) && (int) $latency > 0)
            ->map(fn($latency): int => (int) $latency)
            ->values()
            ->all();

        $highLatencyCount = collect($latencyValues)
            ->filter(fn(int $latency): bool => $latency >= 8000)
            ->count();

        return [
            'llm_failure_rate_percent' => round(($llmFailureCount / $totalCount) * 100, 1),
            'fallback_rate_percent' => round(($fallbackCount / $totalCount) * 100, 1),
            'high_latency_rate_percent' => round(($highLatencyCount / $totalCount) * 100, 1),
            'latency_p50_ms' => $this->percentile($latencyValues, 50),
            'latency_p90_ms' => $this->percentile($latencyValues, 90),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractTopProviders(Collection $feedbackRows): array
    {
        $providerCounts = $feedbackRows
            ->map(function (AiAssistantFeedback $feedback): string {
                $provider = strtolower(trim((string) $feedback->provider));

                return $provider !== '' ? $provider : 'rule_based';
            })
            ->countBy()
            ->sortDesc();

        return $providerCounts
            ->take(3)
            ->map(fn(int $count, string $provider): string => $provider . ':' . $count)
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $reasonCodes
     * @return array<int, string>
     */
    private function extractTopReasonCodes(array $reasonCodes, int $limit = 6): array
    {
        $frequencies = [];

        foreach ($reasonCodes as $reasonCode) {
            $normalizedReasonCode = trim(strtolower($reasonCode));
            if ($normalizedReasonCode === '') {
                continue;
            }

            $frequencies[$normalizedReasonCode] = ($frequencies[$normalizedReasonCode] ?? 0) + 1;
        }

        if (count($frequencies) === 0) {
            return [];
        }

        arsort($frequencies);

        return array_slice(array_keys($frequencies), 0, max(1, $limit));
    }

    /**
     * @param array<int, string> $reasons
     * @return array<int, string>
     */
    private function extractTopKeywords(array $reasons, int $limit = 6): array
    {
        $stopWords = [
            'yang',
            'untuk',
            'dengan',
            'karena',
            'sudah',
            'belum',
            'saya',
            'aku',
            'kamu',
            'kak',
            'admin',
            'agar',
            'atau',
            'dan',
            'dari',
            'pada',
            'jadi',
            'supaya',
            'dalam',
            'saat',
            'ini',
            'itu',
            'tidak',
            'gak',
            'ga',
            'nggak',
            'bisa',
            'banget',
            'masih',
            'kalau',
            'kalo',
            'lagi',
            'sih',
            'produk',
            'barang',
            'pesanan',
            'order',
            'tolong',
            'mohon',
            'minta',
            'lebih',
            'kurang',
            'udah',
            'sekali',
            'aja',
            'juga',
            'nih',
            'dong',
            'deh',
        ];

        $frequencies = [];

        foreach ($reasons as $reason) {
            $normalizedReason = strtolower($this->normalizeReason($reason));
            $tokenizedReason = preg_replace('/[^a-z0-9\s]/', ' ', $normalizedReason);
            $tokens = preg_split('/\s+/', trim((string) $tokenizedReason)) ?: [];

            foreach ($tokens as $token) {
                if ($token === '' || strlen($token) < 4) {
                    continue;
                }

                if (in_array($token, $stopWords, true)) {
                    continue;
                }

                $frequencies[$token] = ($frequencies[$token] ?? 0) + 1;
            }
        }

        if (count($frequencies) === 0) {
            return [];
        }

        arsort($frequencies);

        return array_slice(array_keys($frequencies), 0, max(1, $limit));
    }

    /**
     * @param array<int, string> $topReasonCodes
     * @param array<string, mixed> $qualitySignals
     */
    private function calculateConfidenceScore(int $signalCount, array $topReasonCodes = [], array $qualitySignals = []): float
    {
        $boundedSignalCount = max(1, min(100, $signalCount));

        $baseScore = 0.42 + ($boundedSignalCount * 0.025);
        $reasonCodeBoost = min(0.08, count($topReasonCodes) * 0.015);

        $runtimeBoost = 0.0;
        if ((float) ($qualitySignals['llm_failure_rate_percent'] ?? 0.0) >= 30) {
            $runtimeBoost += 0.04;
        }

        if ((float) ($qualitySignals['high_latency_rate_percent'] ?? 0.0) >= 30) {
            $runtimeBoost += 0.03;
        }

        $confidenceScore = $baseScore + $reasonCodeBoost + $runtimeBoost;

        return round(min(0.99, $confidenceScore), 2);
    }

    /**
     * @param array<int, int> $values
     */
    private function percentile(array $values, int $percentile): ?int
    {
        if (count($values) === 0) {
            return null;
        }

        sort($values);

        $clampedPercentile = max(0, min(100, $percentile));
        $index = (int) floor((($clampedPercentile / 100) * (count($values) - 1)));

        return $values[$index] ?? null;
    }
}
