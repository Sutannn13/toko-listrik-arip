<?php

namespace App\Services\Ai;

use App\Models\AiAssistantFeedback;
use App\Models\AiPromptLearningRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiPromptLearningService
{
    private const CACHE_TTL_SECONDS = 300;

    private const DEFAULT_ADAPTIVE_RULE_CONTEXT = '- Belum ada rule adaptif aktif dari feedback negatif terbaru.';

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
            ];
        }

        $startAt = now()->subDays($boundedLookbackDays);

        $negativeFeedbackRows = AiAssistantFeedback::query()
            ->where('rating', -1)
            ->where('created_at', '>=', $startAt)
            ->whereNotNull('reason')
            ->where('reason', '!=', '')
            ->get(['intent', 'reason', 'created_at']);

        $groupedByIntent = $negativeFeedbackRows->groupBy(function (AiAssistantFeedback $feedback): string {
            return $this->normalizeIntent((string) $feedback->intent);
        });

        $activeRuleKeys = [];
        $updatedRuleCount = 0;

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

            if (count($normalizedReasons) === 0) {
                continue;
            }

            $topKeywords = $this->extractTopKeywords($normalizedReasons, 6);
            $confidenceScore = $this->calculateConfidenceScore($signalCount);
            $ruleKey = 'negative_feedback:' . $intent;

            AiPromptLearningRule::query()->updateOrCreate(
                ['rule_key' => $ruleKey],
                [
                    'intent' => $intent,
                    'source' => 'negative_feedback',
                    'trigger_keywords' => $topKeywords,
                    'directive' => $this->buildDirectiveForIntent($intent, $topKeywords),
                    'negative_feedback_count' => $signalCount,
                    'sample_count' => $signalCount,
                    'confidence_score' => $confidenceScore,
                    'lookback_days' => $boundedLookbackDays,
                    'last_learned_at' => now(),
                    'is_active' => true,
                    'metrics' => [
                        'top_keywords' => $topKeywords,
                        'examples' => array_values(array_slice($normalizedReasons, 0, 3)),
                    ],
                ],
            );

            $activeRuleKeys[] = $ruleKey;
            $updatedRuleCount++;
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

    private function compilePromptAdjustmentContext(): string
    {
        $activeRules = AiPromptLearningRule::query()
            ->where('is_active', true)
            ->where('source', 'negative_feedback')
            ->orderByDesc('confidence_score')
            ->orderByDesc('negative_feedback_count')
            ->limit(8)
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

            $lines[] = '- Intent ' . $intentLabel
                . ' | sinyal negatif: ' . $negativeFeedbackCount
                . ' | confidence: ' . $confidenceScore
                . ' | keyword: ' . $keywordLabel
                . ' | aturan: ' . trim((string) $activeRule->directive);
        }

        return implode("\n", $lines);
    }

    private function buildDirectiveForIntent(string $intent, array $topKeywords): string
    {
        $keywordHint = count($topKeywords) > 0
            ? 'Perhatikan kata kunci masalah: ' . implode(', ', array_slice($topKeywords, 0, 5)) . '.'
            : 'Perhatikan pola keluhan terbaru pengguna.';

        return match ($intent) {
            'website_help' => 'Untuk website_help, jawab step-by-step sesuai halaman aktif, hindari jawaban umum tanpa langkah operasional. ' . $keywordHint,
            'order_tracking' => 'Untuk order_tracking, selalu verifikasi identitas user lalu berikan status terbaru, next action, dan jalur eskalasi jika terjadi kendala. ' . $keywordHint,
            'product_recommendation' => 'Untuk product_recommendation, kurangi jawaban generik; pilih produk berdasarkan budget, konteks ruangan, dan jelaskan alasan pemilihan produk. ' . $keywordHint,
            'troubleshooting' => 'Untuk troubleshooting, mulai dari empati singkat lalu diagnosis 1-3 langkah mandiri sebelum eskalasi ke admin. ' . $keywordHint,
            default => 'Perjelas jawaban sesuai konteks user, ringkas namun actionable, dan hindari asumsi tanpa data. ' . $keywordHint,
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

    private function calculateConfidenceScore(int $signalCount): float
    {
        $boundedSignalCount = max(1, min(100, $signalCount));
        $confidenceScore = 0.45 + ($boundedSignalCount * 0.03);

        return round(min(0.98, $confidenceScore), 2);
    }
}
