<?php

namespace App\Console\Commands;

use App\Models\AiAssistantFeedback;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GenerateAiWeeklyQualityReport extends Command
{
    protected $signature = 'ai:report-weekly
        {--as-of= : Tanggal acuan laporan (YYYY-MM-DD)}
        {--days=7 : Panjang window laporan (7/14/30)}
        {--output-dir= : Direktori output laporan}';

    protected $description = 'Generate auto-report mingguan AI assistant (JSON + ringkasan teks) dengan analisis reason_code negatif, perubahan WoW, dan rekomendasi aksi prioritas';

    public function handle(): int
    {
        if (! Schema::hasTable('ai_assistant_feedback')) {
            $this->error('Tabel ai_assistant_feedback belum tersedia. Jalankan migrasi terlebih dahulu.');

            return 1;
        }

        $requiredColumns = [
            'reason_code',
            'provider',
            'model',
            'llm_status',
            'fallback_used',
            'response_latency_ms',
        ];

        $missingColumns = collect($requiredColumns)
            ->reject(fn(string $column): bool => Schema::hasColumn('ai_assistant_feedback', $column))
            ->values()
            ->all();

        if ($missingColumns !== []) {
            $this->error('Skema feedback v2 belum lengkap. Kolom belum tersedia: ' . implode(', ', $missingColumns) . '. Jalankan php artisan migrate.');

            return 1;
        }

        $windowDays = max(7, min(30, (int) $this->option('days')));
        $asOfDate = $this->resolveAsOfDate();

        if (! $asOfDate instanceof Carbon) {
            return 1;
        }

        $currentWindowStart = $asOfDate->copy()->subDays($windowDays - 1)->startOfDay();
        $currentWindowEnd = $asOfDate->copy()->endOfDay();

        $previousWindowEnd = $currentWindowStart->copy()->subSecond();
        $previousWindowStart = $previousWindowEnd->copy()->subDays($windowDays - 1)->startOfDay();

        $currentNegativeRows = AiAssistantFeedback::query()
            ->where('rating', -1)
            ->whereBetween('created_at', [$currentWindowStart, $currentWindowEnd])
            ->get([
                'reason_code',
                'reason',
                'intent',
                'provider',
                'model',
                'llm_status',
                'fallback_used',
                'response_latency_ms',
                'created_at',
            ]);

        $previousNegativeRows = AiAssistantFeedback::query()
            ->where('rating', -1)
            ->whereBetween('created_at', [$previousWindowStart, $previousWindowEnd])
            ->get([
                'reason_code',
                'reason',
                'intent',
                'provider',
                'model',
                'llm_status',
                'fallback_used',
                'response_latency_ms',
                'created_at',
            ]);

        $currentFeedbackRows = AiAssistantFeedback::query()
            ->whereBetween('created_at', [$currentWindowStart, $currentWindowEnd])
            ->whereIn('rating', [-1, 1])
            ->get(['rating']);

        $previousFeedbackRows = AiAssistantFeedback::query()
            ->whereBetween('created_at', [$previousWindowStart, $previousWindowEnd])
            ->whereIn('rating', [-1, 1])
            ->get(['rating']);

        $currentBreakdown = $this->buildReasonCodeBreakdown($currentNegativeRows);
        $previousBreakdown = $this->buildReasonCodeBreakdown($previousNegativeRows);

        $topNegativeReasonCodes = $this->buildTopReasonCodeRows($currentBreakdown, $previousBreakdown, 10);
        $weekOverWeekChanges = $this->buildWeekOverWeekRows($currentBreakdown, $previousBreakdown, 20);

        $runtimeSignalsCurrent = $this->collectRuntimeSignals($currentNegativeRows);
        $recommendations = $this->buildPriorityRecommendations(
            $topNegativeReasonCodes,
            $runtimeSignalsCurrent,
            (int) $currentNegativeRows->count(),
            (int) $previousNegativeRows->count(),
        );

        $totalNegativeCurrent = (int) $currentNegativeRows->count();
        $totalNegativePrevious = (int) $previousNegativeRows->count();

        $helpfulRateCurrent = $this->calculateHelpfulRate($currentFeedbackRows);
        $helpfulRatePrevious = $this->calculateHelpfulRate($previousFeedbackRows);
        $helpfulRateDelta = round($helpfulRateCurrent - $helpfulRatePrevious, 2);

        $negativeDeltaCount = $totalNegativeCurrent - $totalNegativePrevious;
        $negativeDeltaPercent = $this->calculateDeltaPercent($totalNegativeCurrent, $totalNegativePrevious);

        $reportStatus = $this->resolveReportStatus(
            $totalNegativeCurrent,
            $totalNegativePrevious,
            $runtimeSignalsCurrent,
        );

        $outputDirectory = trim((string) $this->option('output-dir'));
        if ($outputDirectory === '') {
            $outputDirectory = storage_path('app/ai-reports/weekly');
        }

        try {
            File::ensureDirectoryExists($outputDirectory);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Gagal menyiapkan direktori output laporan: ' . $outputDirectory);

            return 1;
        }

        $reportTag = $asOfDate->format('Ymd') . '-d' . $windowDays;
        $jsonPath = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ai-weekly-report-' . $reportTag . '.json';
        $summaryPath = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ai-weekly-summary-' . $reportTag . '.txt';

        $report = [
            'version' => 'ai-weekly-quality-report-v1',
            'generated_at' => now()->toISOString(),
            'status' => $reportStatus,
            'window' => [
                'days' => $windowDays,
                'as_of' => $asOfDate->toDateString(),
                'current' => [
                    'start' => $currentWindowStart->toISOString(),
                    'end' => $currentWindowEnd->toISOString(),
                    'label' => $currentWindowStart->format('d M Y') . ' - ' . $currentWindowEnd->format('d M Y'),
                ],
                'previous' => [
                    'start' => $previousWindowStart->toISOString(),
                    'end' => $previousWindowEnd->toISOString(),
                    'label' => $previousWindowStart->format('d M Y') . ' - ' . $previousWindowEnd->format('d M Y'),
                ],
            ],
            'summary' => [
                'total_negative_current' => $totalNegativeCurrent,
                'total_negative_previous' => $totalNegativePrevious,
                'negative_delta_count' => $negativeDeltaCount,
                'negative_delta_percent' => $negativeDeltaPercent,
                'helpful_rate_current' => $helpfulRateCurrent,
                'helpful_rate_previous' => $helpfulRatePrevious,
                'helpful_rate_delta' => $helpfulRateDelta,
            ],
            'runtime_signals_current' => $runtimeSignalsCurrent,
            'top_negative_reason_codes' => $topNegativeReasonCodes,
            'week_over_week_changes' => $weekOverWeekChanges,
            'recommendations' => $recommendations,
            'artifacts' => [
                [
                    'type' => 'json_report',
                    'path' => $jsonPath,
                ],
                [
                    'type' => 'text_summary',
                    'path' => $summaryPath,
                ],
            ],
        ];

        $summaryText = $this->buildSummaryText($report);

        try {
            File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            File::put($summaryPath, $summaryText);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Gagal menulis file laporan mingguan.');

            return 1;
        }

        $this->info('Auto-report mingguan AI assistant berhasil dibuat.');
        $this->line('Status: ' . $reportStatus);
        $this->line('Negative WoW: ' . $negativeDeltaCount . ' (' . ($negativeDeltaPercent !== null ? number_format($negativeDeltaPercent, 2) . '%' : '-') . ')');
        $this->line('JSON report: ' . $jsonPath);
        $this->line('Text summary: ' . $summaryPath);

        return 0;
    }

    private function resolveAsOfDate(): ?Carbon
    {
        $rawAsOf = trim((string) $this->option('as-of'));

        if ($rawAsOf === '') {
            return now();
        }

        try {
            return Carbon::parse($rawAsOf);
        } catch (Throwable) {
            $this->error('Nilai --as-of tidak valid. Gunakan format tanggal yang dapat diparse, contoh: 2026-04-20');

            return null;
        }
    }

    /**
     * @return array<string, int>
     */
    private function buildReasonCodeBreakdown(Collection $rows): array
    {
        $breakdown = [];

        foreach ($rows as $row) {
            if (! $row instanceof AiAssistantFeedback) {
                continue;
            }

            $normalizedReasonCode = $this->normalizeReasonCode((string) $row->reason_code, (string) $row->reason);

            $breakdown[$normalizedReasonCode] = ($breakdown[$normalizedReasonCode] ?? 0) + 1;
        }

        arsort($breakdown);

        return $breakdown;
    }

    /**
     * @param array<string, int> $currentBreakdown
     * @param array<string, int> $previousBreakdown
     * @return array<int, array<string, mixed>>
     */
    private function buildTopReasonCodeRows(array $currentBreakdown, array $previousBreakdown, int $limit): array
    {
        $rows = [];

        foreach (array_slice($currentBreakdown, 0, max(1, $limit), true) as $reasonCode => $currentCount) {
            $previousCount = (int) ($previousBreakdown[$reasonCode] ?? 0);
            $deltaCount = $currentCount - $previousCount;

            $rows[] = [
                'reason_code' => $reasonCode,
                'display_label' => $this->resolveReasonCodeDisplayLabel($reasonCode),
                'current_count' => (int) $currentCount,
                'previous_count' => $previousCount,
                'delta_count' => $deltaCount,
                'delta_percent' => $this->calculateDeltaPercent((int) $currentCount, $previousCount),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, int> $currentBreakdown
     * @param array<string, int> $previousBreakdown
     * @return array<int, array<string, mixed>>
     */
    private function buildWeekOverWeekRows(array $currentBreakdown, array $previousBreakdown, int $limit): array
    {
        $reasonCodes = array_values(array_unique(array_merge(array_keys($currentBreakdown), array_keys($previousBreakdown))));

        $rows = [];
        foreach ($reasonCodes as $reasonCode) {
            $currentCount = (int) ($currentBreakdown[$reasonCode] ?? 0);
            $previousCount = (int) ($previousBreakdown[$reasonCode] ?? 0);
            $deltaCount = $currentCount - $previousCount;

            $rows[] = [
                'reason_code' => $reasonCode,
                'display_label' => $this->resolveReasonCodeDisplayLabel($reasonCode),
                'current_count' => $currentCount,
                'previous_count' => $previousCount,
                'delta_count' => $deltaCount,
                'delta_percent' => $this->calculateDeltaPercent($currentCount, $previousCount),
                'absolute_delta_count' => abs($deltaCount),
            ];
        }

        usort($rows, function (array $left, array $right): int {
            $absoluteDeltaCompare = (int) (($right['absolute_delta_count'] ?? 0) <=> ($left['absolute_delta_count'] ?? 0));
            if ($absoluteDeltaCompare !== 0) {
                return $absoluteDeltaCompare;
            }

            return (int) (($right['current_count'] ?? 0) <=> ($left['current_count'] ?? 0));
        });

        return array_values(array_slice($rows, 0, max(1, $limit)));
    }

    /**
     * @return array<string, mixed>
     */
    private function collectRuntimeSignals(Collection $rows): array
    {
        $totalCount = max(1, $rows->count());

        $failureStatuses = [
            'primary_failed',
            'fallback_failed',
            'prompt_build_failed',
            'fallback_unavailable',
            'budget_exhausted',
            'fallback_budget_exhausted',
        ];

        $llmFailureCount = $rows
            ->filter(function ($row) use ($failureStatuses): bool {
                if (! $row instanceof AiAssistantFeedback) {
                    return false;
                }

                $status = strtolower(trim((string) $row->llm_status));

                return in_array($status, $failureStatuses, true);
            })
            ->count();

        $fallbackCount = $rows
            ->filter(fn($row): bool => $row instanceof AiAssistantFeedback && (bool) $row->fallback_used)
            ->count();

        $latencyValues = $rows
            ->filter(fn($row): bool => $row instanceof AiAssistantFeedback)
            ->pluck('response_latency_ms')
            ->filter(fn($value): bool => is_numeric($value) && (int) $value > 0)
            ->map(fn($value): int => (int) $value)
            ->values()
            ->all();

        $highLatencyCount = collect($latencyValues)
            ->filter(fn(int $latency): bool => $latency >= 8000)
            ->count();

        return [
            'samples' => $rows->count(),
            'llm_failure_rate_percent' => round(($llmFailureCount / $totalCount) * 100, 1),
            'fallback_rate_percent' => round(($fallbackCount / $totalCount) * 100, 1),
            'high_latency_rate_percent' => round(($highLatencyCount / $totalCount) * 100, 1),
            'latency_p50_ms' => $this->percentile($latencyValues, 50),
            'latency_p90_ms' => $this->percentile($latencyValues, 90),
        ];
    }

    private function calculateHelpfulRate(Collection $feedbackRows): float
    {
        $totalCount = $feedbackRows->count();

        if ($totalCount === 0) {
            return 0.0;
        }

        $helpfulCount = $feedbackRows
            ->pluck('rating')
            ->filter(fn($rating): bool => (int) $rating === 1)
            ->count();

        return round(($helpfulCount / $totalCount) * 100, 2);
    }

    private function calculateDeltaPercent(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            if ($current <= 0) {
                return 0.0;
            }

            return null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * @param array<int, array<string, mixed>> $topRows
     * @param array<string, mixed> $runtimeSignals
     * @return array<int, array<string, mixed>>
     */
    private function buildPriorityRecommendations(array $topRows, array $runtimeSignals, int $totalCurrent, int $totalPrevious): array
    {
        $recommendationsByActionKey = [];

        foreach ($topRows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $reasonCode = (string) ($row['reason_code'] ?? 'unknown');
            $actionTemplate = $this->resolveActionTemplateForReasonCode($reasonCode);
            $actionKey = (string) $actionTemplate['action_key'];

            $currentCount = (int) ($row['current_count'] ?? 0);
            $deltaCount = (int) ($row['delta_count'] ?? 0);
            $deltaPercent = is_numeric($row['delta_percent'] ?? null) ? (float) $row['delta_percent'] : null;

            $score = ($currentCount * 2.0) + max(0, $deltaCount) * 1.5;
            if ($deltaPercent !== null && $deltaPercent >= 50) {
                $score += 3.0;
            }

            if (! array_key_exists($actionKey, $recommendationsByActionKey)) {
                $recommendationsByActionKey[$actionKey] = [
                    'action_key' => $actionKey,
                    'title' => (string) $actionTemplate['title'],
                    'summary' => (string) $actionTemplate['summary'],
                    'recommended_steps' => (array) ($actionTemplate['steps'] ?? []),
                    'impact_score' => 0.0,
                    'impacted_reason_codes' => [],
                    'evidence' => [],
                ];
            }

            $recommendationsByActionKey[$actionKey]['impact_score'] += $score;

            if (! in_array($reasonCode, $recommendationsByActionKey[$actionKey]['impacted_reason_codes'], true)) {
                $recommendationsByActionKey[$actionKey]['impacted_reason_codes'][] = $reasonCode;
            }

            $recommendationsByActionKey[$actionKey]['evidence'][] = [
                'reason_code' => $reasonCode,
                'current_count' => $currentCount,
                'delta_count' => $deltaCount,
                'delta_percent' => $deltaPercent,
            ];
        }

        $llmFailureRate = (float) ($runtimeSignals['llm_failure_rate_percent'] ?? 0.0);
        if ($llmFailureRate >= 20) {
            $recommendationsByActionKey['provider_reliability_hardening'] = [
                'action_key' => 'provider_reliability_hardening',
                'title' => 'Hardening stabilitas provider LLM',
                'summary' => 'Failure rate provider relatif tinggi. Perlu tuning retry, fallback, dan guard agar jawaban tetap actionable saat provider tidak stabil.',
                'recommended_steps' => [
                    'Naikkan observability status provider per intent (primary_failed/fallback_failed).',
                    'Tuning timeout dan fallback priority untuk intent dengan failure tertinggi.',
                    'Tambah test skenario degradasi provider agar response tool-first tetap aman.',
                ],
                'impact_score' => 8.0 + ($llmFailureRate / 10),
                'impacted_reason_codes' => ['provider_error', 'not_helpful_timeout'],
                'evidence' => [
                    ['metric' => 'llm_failure_rate_percent', 'value' => $llmFailureRate],
                ],
            ];
        }

        $highLatencyRate = (float) ($runtimeSignals['high_latency_rate_percent'] ?? 0.0);
        if ($highLatencyRate >= 20) {
            $recommendationsByActionKey['latency_budget_optimization'] = [
                'action_key' => 'latency_budget_optimization',
                'title' => 'Optimasi budget latensi respon',
                'summary' => 'Proporsi latency tinggi meningkat. Perlu pengurangan prompt verbosity dan optimasi jalur provider.',
                'recommended_steps' => [
                    'Audit prompt panjang per intent dan pangkas konteks non-kritis.',
                    'Review max_input_tokens dan target latency p90.',
                    'Prioritaskan jawaban ringkas untuk website_help/troubleshooting saat beban tinggi.',
                ],
                'impact_score' => 7.0 + ($highLatencyRate / 10),
                'impacted_reason_codes' => ['not_helpful_slow_response', 'not_helpful_latency'],
                'evidence' => [
                    ['metric' => 'high_latency_rate_percent', 'value' => $highLatencyRate],
                ],
            ];
        }

        if ($totalPrevious > 0) {
            $growthPercent = (($totalCurrent - $totalPrevious) / $totalPrevious) * 100;
            if ($growthPercent >= 20) {
                $recommendationsByActionKey['weekly_stabilization_sprint'] = [
                    'action_key' => 'weekly_stabilization_sprint',
                    'title' => 'Stabilization sprint 1 minggu',
                    'summary' => 'Total feedback negatif meningkat signifikan week-over-week. Butuh sprint stabilisasi fokus intent prioritas tertinggi.',
                    'recommended_steps' => [
                        'Lock top 3 reason_code negatif sebagai prioritas sprint.',
                        'Rilis patch kecil bertahap (intent, prompt rule, fallback).',
                        'Evaluasi ulang daily benchmark tiap pagi dan track delta harian.',
                    ],
                    'impact_score' => 9.0 + max(0.0, ($growthPercent / 20)),
                    'impacted_reason_codes' => [],
                    'evidence' => [
                        ['metric' => 'negative_growth_percent', 'value' => round($growthPercent, 2)],
                    ],
                ];
            }
        }

        $recommendations = array_values($recommendationsByActionKey);

        usort($recommendations, function (array $left, array $right): int {
            return (float) ($right['impact_score'] ?? 0.0) <=> (float) ($left['impact_score'] ?? 0.0);
        });

        $recommendations = array_slice($recommendations, 0, 5);

        foreach ($recommendations as $index => &$recommendation) {
            $recommendation['priority'] = 'P' . ($index + 1);
            $recommendation['impact_score'] = round((float) ($recommendation['impact_score'] ?? 0.0), 2);
        }

        return $recommendations;
    }

    /**
     * @return array{action_key: string, title: string, summary: string, steps: array<int, string>}
     */
    private function resolveActionTemplateForReasonCode(string $reasonCode): array
    {
        if ($this->containsAny($reasonCode, ['wrong_intent', 'language_mismatch', 'order_status_confusion', 'checkout_flow_confusion'])) {
            return [
                'action_key' => 'intent_router_tuning',
                'title' => 'Tuning intent routing dan trigger phrase',
                'summary' => 'Keluhan mengarah ke salah klasifikasi intent. AI perlu pembobotan ulang keyword dan contoh intent ambigu.',
                'steps' => [
                    'Tambahkan phrase intent ambigu baru ke router (contoh: riwayat barang/riwayat transaksi).',
                    'Tambah benchmark case untuk intent yang sering miss-classification.',
                    'Review fallback intent ke website_help agar tidak lari ke jawaban kontak toko.',
                ],
            ];
        }

        if ($this->containsAny($reasonCode, ['payment', 'proof', 'checkout'])) {
            return [
                'action_key' => 'payment_checkout_flow_fix',
                'title' => 'Perbaikan alur jawaban pembayaran dan checkout',
                'summary' => 'Pengguna butuh jawaban step-by-step yang lebih operasional untuk alur transaksi.',
                'steps' => [
                    'Perjelas template jawaban langkah pembayaran/unggah bukti.',
                    'Prioritaskan jawaban yang menyebut menu/halaman yang harus dibuka user.',
                    'Tambahkan contoh respons ringkas untuk skenario error pembayaran yang berulang.',
                ],
            ];
        }

        if ($this->containsAny($reasonCode, ['privacy', 'security', 'trust'])) {
            return [
                'action_key' => 'privacy_trust_script_hardening',
                'title' => 'Hardening script privasi dan trust assurance',
                'summary' => 'Pertanyaan sensitif privasi/keamanan perlu jawaban yang konsisten dan meyakinkan.',
                'steps' => [
                    'Standarkan kalimat jaminan privasi sesuai kebijakan toko.',
                    'Pastikan fallback answer tetap tegas dan tidak ambigu untuk isu keamanan data.',
                    'Tambah benchmark khusus pertanyaan privasi bukti transfer.',
                ],
            ];
        }

        if ($this->containsAny($reasonCode, ['slow_response', 'latency', 'timeout'])) {
            return [
                'action_key' => 'latency_response_optimization',
                'title' => 'Optimasi kecepatan dan ketepatan respon',
                'summary' => 'Sinyal performa menunjukkan jawaban sering terlambat atau timeout.',
                'steps' => [
                    'Pangkas konteks prompt yang tidak esensial pada intent ber-volume tinggi.',
                    'Optimasi fallback agar langsung memberi jawaban inti saat provider lambat.',
                    'Pantau p90 latency per intent dan tetapkan target per minggu.',
                ],
            ];
        }

        if ($this->containsAny($reasonCode, ['hallucination', 'outdated', 'policy_conflict', 'stock_confusion', 'price_confusion'])) {
            return [
                'action_key' => 'knowledge_grounding_refresh',
                'title' => 'Refresh grounding knowledge dan validasi fakta',
                'summary' => 'Konten jawaban perlu diketatkan agar tidak melenceng dari data produk/kebijakan terbaru.',
                'steps' => [
                    'Perbarui sumber knowledge produk, stok, harga, dan policy internal.',
                    'Perketat guardrail agar AI menghindari asumsi tanpa data.',
                    'Tambah benchmark anti-hallucination pada pertanyaan harga/stok/kebijakan.',
                ],
            ];
        }

        if ($this->containsAny($reasonCode, ['too_short', 'too_verbose', 'repetition', 'tone'])) {
            return [
                'action_key' => 'response_style_tuning',
                'title' => 'Tuning style jawaban agar lebih natural',
                'summary' => 'Masalah lebih dominan pada gaya bahasa, struktur, dan panjang respons.',
                'steps' => [
                    'Set guideline panjang jawaban per intent agar tidak terlalu pendek/panjang.',
                    'Kurangi repetisi dengan template respons intent-specific.',
                    'Review tone agar tetap ramah, ringkas, dan operasional.',
                ],
            ];
        }

        return [
            'action_key' => 'generic_quality_triage',
            'title' => 'Triage kualitas jawaban umum',
            'summary' => 'Ditemukan reason_code negatif yang belum dominan pada kategori tertentu, perlu triase manual terstruktur.',
            'steps' => [
                'Review 20 sampel percakapan terbaru pada reason_code ini.',
                'Kelompokkan ke issue intent, konten, latency, atau trust.',
                'Buat patch terukur lalu validasi di benchmark harian.',
            ],
        ];
    }

    private function containsAny(string $value, array $needles): bool
    {
        $normalizedValue = strtolower(trim($value));

        foreach ($needles as $needle) {
            if (str_contains($normalizedValue, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function resolveReasonCodeDisplayLabel(string $reasonCode): string
    {
        $labelMap = [
            'helpful_generic' => 'Jawaban membantu (umum)',
            'not_helpful_generic' => 'Jawaban kurang membantu (umum)',
            'not_helpful_wrong_intent' => 'Intent terdeteksi kurang tepat',
            'not_helpful_incomplete_answer' => 'Jawaban belum lengkap atau terpotong',
            'not_helpful_hallucination' => 'Informasi kurang akurat',
            'not_helpful_payment_instruction' => 'Instruksi pembayaran kurang jelas',
            'not_helpful_slow_response' => 'Respon terlalu lambat',
            'not_helpful_timeout' => 'Respon timeout atau gagal dimuat',
            'not_helpful_price_confusion' => 'Informasi harga membingungkan',
            'not_helpful_stock_confusion' => 'Informasi stok membingungkan',
            'not_helpful_checkout_flow_confusion' => 'Alur checkout tidak jelas',
            'not_helpful_order_status_confusion' => 'Status pesanan tidak jelas',
            'not_helpful_payment_proof_confusion' => 'Instruksi bukti pembayaran membingungkan',
            'privacy_concern' => 'Kekhawatiran privasi data',
            'security_concern' => 'Kekhawatiran keamanan',
            'provider_error' => 'Gangguan provider AI',
            'provider_rate_limited' => 'Provider terkena batas rate limit',
            'provider_billing_issue' => 'Provider terkendala billing',
            'budget_exhausted' => 'Budget AI harian habis',
            'prompt_build_failed' => 'Penyusunan prompt gagal',
        ];

        if (array_key_exists($reasonCode, $labelMap)) {
            return $labelMap[$reasonCode];
        }

        return ucwords(str_replace('_', ' ', $reasonCode));
    }

    private function normalizeReasonCode(string $reasonCode, string $reasonFallback = ''): string
    {
        $normalizedReasonCode = strtolower(trim($reasonCode));
        $normalizedReasonCode = str_replace(['-', ' '], '_', $normalizedReasonCode);
        $normalizedReasonCode = preg_replace('/[^a-z0-9_]/', '_', $normalizedReasonCode) ?: '';
        $normalizedReasonCode = preg_replace('/_+/', '_', $normalizedReasonCode) ?: '';
        $normalizedReasonCode = trim($normalizedReasonCode, '_');

        if ($normalizedReasonCode !== '') {
            return $normalizedReasonCode;
        }

        $normalizedReason = strtolower(trim((string) preg_replace('/\s+/', ' ', strip_tags($reasonFallback))));

        if ($normalizedReason === '') {
            return 'not_helpful_generic';
        }

        if (str_contains($normalizedReason, 'lambat') || str_contains($normalizedReason, 'lemot') || str_contains($normalizedReason, 'lama')) {
            return 'not_helpful_slow_response';
        }

        if (str_contains($normalizedReason, 'intent') || str_contains($normalizedReason, 'meleset')) {
            return 'not_helpful_wrong_intent';
        }

        if (str_contains($normalizedReason, 'kurang jelas') || str_contains($normalizedReason, 'membingungkan')) {
            return 'not_helpful_incomplete_answer';
        }

        if (str_contains($normalizedReason, 'salah') || str_contains($normalizedReason, 'ngaco') || str_contains($normalizedReason, 'tidak akurat')) {
            return 'not_helpful_hallucination';
        }

        return 'not_helpful_generic';
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

    /**
     * @param array<string, mixed> $report
     */
    private function buildSummaryText(array $report): string
    {
        $summary = (array) ($report['summary'] ?? []);
        $window = (array) ($report['window'] ?? []);
        $currentWindow = (array) ($window['current'] ?? []);
        $previousWindow = (array) ($window['previous'] ?? []);
        $topRows = (array) ($report['top_negative_reason_codes'] ?? []);
        $wowRows = (array) ($report['week_over_week_changes'] ?? []);
        $recommendations = (array) ($report['recommendations'] ?? []);

        $lines = [];
        $lines[] = 'AI Weekly Quality Report';
        $lines[] = 'Generated at: ' . (string) ($report['generated_at'] ?? '-');
        $lines[] = 'Status: ' . (string) ($report['status'] ?? 'unknown');
        $lines[] = 'Window: ' . (string) ($window['days'] ?? '-') . ' hari';
        $lines[] = 'Periode current: ' . (string) ($currentWindow['label'] ?? '-');
        $lines[] = 'Periode previous: ' . (string) ($previousWindow['label'] ?? '-');
        $lines[] = '';

        $lines[] = 'Ringkasan Utama';
        $lines[] = '- Total negatif current: ' . number_format((int) ($summary['total_negative_current'] ?? 0));
        $lines[] = '- Total negatif previous: ' . number_format((int) ($summary['total_negative_previous'] ?? 0));
        $lines[] = '- Delta negatif: ' . number_format((int) ($summary['negative_delta_count'] ?? 0))
            . ' (' . (is_numeric($summary['negative_delta_percent'] ?? null) ? number_format((float) $summary['negative_delta_percent'], 2) . '%' : '-') . ')';
        $lines[] = '- Helpful rate current: ' . number_format((float) ($summary['helpful_rate_current'] ?? 0), 2) . '%';
        $lines[] = '- Helpful rate previous: ' . number_format((float) ($summary['helpful_rate_previous'] ?? 0), 2) . '%';
        $lines[] = '- Helpful rate delta: ' . number_format((float) ($summary['helpful_rate_delta'] ?? 0), 2) . '%';
        $lines[] = '';

        $lines[] = 'Top 10 reason_code negatif';
        if (count($topRows) === 0) {
            $lines[] = '- Tidak ada data feedback negatif pada periode current.';
        } else {
            foreach ($topRows as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $lines[] = ($index + 1) . '. '
                    . (string) ($row['display_label'] ?? (string) ($row['reason_code'] ?? '-'))
                    . ' [' . (string) ($row['reason_code'] ?? '-') . ']'
                    . ' | current=' . number_format((int) ($row['current_count'] ?? 0))
                    . ' | previous=' . number_format((int) ($row['previous_count'] ?? 0))
                    . ' | delta=' . number_format((int) ($row['delta_count'] ?? 0))
                    . ' | WoW=' . (is_numeric($row['delta_percent'] ?? null) ? number_format((float) $row['delta_percent'], 2) . '%' : '-');
            }
        }

        $lines[] = '';
        $lines[] = 'Perubahan WoW paling signifikan';
        if (count($wowRows) === 0) {
            $lines[] = '- Tidak ada perubahan reason_code yang signifikan.';
        } else {
            foreach ($wowRows as $index => $row) {
                if (! is_array($row) || $index >= 10) {
                    continue;
                }

                $lines[] = ($index + 1) . '. '
                    . (string) ($row['display_label'] ?? (string) ($row['reason_code'] ?? '-'))
                    . ' | delta=' . number_format((int) ($row['delta_count'] ?? 0))
                    . ' | current=' . number_format((int) ($row['current_count'] ?? 0))
                    . ' | previous=' . number_format((int) ($row['previous_count'] ?? 0));
            }
        }

        $lines[] = '';
        $lines[] = 'Rekomendasi aksi prioritas otomatis';
        if (count($recommendations) === 0) {
            $lines[] = '- Belum ada rekomendasi prioritas (data minim).';
        } else {
            foreach ($recommendations as $recommendation) {
                if (! is_array($recommendation)) {
                    continue;
                }

                $lines[] = '[' . (string) ($recommendation['priority'] ?? '-') . '] '
                    . (string) ($recommendation['title'] ?? '-')
                    . ' | score=' . number_format((float) ($recommendation['impact_score'] ?? 0), 2);
                $lines[] = '  Ringkasan: ' . (string) ($recommendation['summary'] ?? '-');

                $steps = is_array($recommendation['recommended_steps'] ?? null)
                    ? $recommendation['recommended_steps']
                    : [];

                foreach (array_slice($steps, 0, 3) as $step) {
                    $lines[] = '  - ' . (string) $step;
                }
            }
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $runtimeSignals
     */
    private function resolveReportStatus(int $currentNegative, int $previousNegative, array $runtimeSignals): string
    {
        if ($currentNegative === 0 && $previousNegative === 0) {
            return 'insufficient_data';
        }

        $llmFailureRate = (float) ($runtimeSignals['llm_failure_rate_percent'] ?? 0.0);

        if ($previousNegative > 0) {
            $growthPercent = (($currentNegative - $previousNegative) / $previousNegative) * 100;
            if ($growthPercent >= 20 || $llmFailureRate >= 25) {
                return 'warning';
            }

            if ($growthPercent >= 5) {
                return 'watch';
            }

            return 'stable';
        }

        if ($currentNegative > 0) {
            return $llmFailureRate >= 25 ? 'warning' : 'watch';
        }

        return 'stable';
    }
}
