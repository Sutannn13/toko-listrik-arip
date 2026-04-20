<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAssistantFeedback;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $now = now();
        $benchmarkWindowOptions = [7, 14, 30];
        $requestedBenchmarkWindowDays = (int) $request->query('benchmark_days', 7);
        $benchmarkWindowDays = in_array($requestedBenchmarkWindowDays, $benchmarkWindowOptions, true)
            ? $requestedBenchmarkWindowDays
            : 7;

        $period30Days = $now->copy()->subDays(30);
        $period7Days = $now->copy()->subDays(6)->startOfDay();
        $trendDays = collect(range(6, 0))->map(fn($offset) => $now->copy()->subDays($offset)->startOfDay());

        $benchmarkPeriodStart = $now->copy()->subDays($benchmarkWindowDays - 1)->startOfDay();
        $benchmarkTrendDays = collect(range($benchmarkWindowDays - 1, 0))
            ->map(fn($offset) => $now->copy()->subDays($offset)->startOfDay());

        $unreadNotificationsCount = 0;

        if (Schema::hasTable('notifications') && $request->user()) {
            $unreadNotificationsCount = $request->user()->unreadNotifications()->count();
        }

        $overview = [
            'total_products' => Product::query()->where('is_active', true)->count(),
            'active_orders' => Order::query()->whereIn('status', ['pending', 'processing', 'shipped'])->count(),
            'total_customers' => User::query()
                ->whereHas('roles', fn($query) => $query
                    ->where('guard_name', 'web')
                    ->where('name', 'user'))
                ->count(),
            'unread_notifications' => $unreadNotificationsCount,
        ];

        $triage = [
            'payments_to_verify' => Order::query()
                ->where('payment_status', 'pending')
                ->whereHas('latestPayment', fn($query) => $query
                    ->whereNotNull('proof_url')
                    ->where('proof_url', '!=', ''))
                ->count(),
            'orders_ready_to_ship' => Order::query()
                ->where('status', 'processing')
                ->where('payment_status', 'paid')
                ->count(),
            'claims_submitted' => WarrantyClaim::query()
                ->where('status', 'submitted')
                ->count(),
            'claims_sla_overdue' => WarrantyClaim::query()
                ->whereIn('status', ['submitted', 'reviewing'])
                ->whereRaw('COALESCE(requested_at, created_at) < ?', [$now->copy()->subHours(48)])
                ->count(),
        ];

        $ordersLast30Days = Order::query()->where('created_at', '>=', $period30Days);

        $totalOrders30Days = (clone $ordersLast30Days)->count();
        $completedOrders30Days = (clone $ordersLast30Days)
            ->where('status', 'completed')
            ->count();

        $paidOrders30Days = (clone $ordersLast30Days)
            ->where('payment_status', 'paid');

        $fulfillmentRate30Days = $totalOrders30Days > 0
            ? round(($completedOrders30Days / $totalOrders30Days) * 100, 1)
            : 0.0;

        $metrics = [
            'paid_revenue_30d' => (int) (clone $paidOrders30Days)->sum('total_amount'),
            'avg_paid_order_value_30d' => (int) round((float) ((clone $paidOrders30Days)->avg('total_amount') ?: 0)),
            'fulfillment_rate_30d' => $fulfillmentRate30Days,
            'open_claims_48h' => WarrantyClaim::query()
                ->whereIn('status', ['submitted', 'reviewing'])
                ->whereRaw('COALESCE(requested_at, created_at) >= ?', [$now->copy()->subHours(48)])
                ->count(),
            'pending_payment_orders' => Order::query()->where('payment_status', 'pending')->count(),
            'failed_payment_orders' => Order::query()->where('payment_status', 'failed')->count(),
        ];

        $revenue7dByDay = Order::query()
            ->selectRaw('DATE(COALESCE(paid_at, created_at)) as day_key, COALESCE(SUM(total_amount), 0) as total')
            ->where('payment_status', 'paid')
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$period7Days])
            ->groupByRaw('DATE(COALESCE(paid_at, created_at))')
            ->pluck('total', 'day_key');

        $claims7dByDay = WarrantyClaim::query()
            ->selectRaw('DATE(COALESCE(requested_at, created_at)) as day_key, COUNT(*) as total')
            ->whereRaw('COALESCE(requested_at, created_at) >= ?', [$period7Days])
            ->groupByRaw('DATE(COALESCE(requested_at, created_at))')
            ->pluck('total', 'day_key');

        $orderBacklog7dByDay = Order::query()
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->whereIn('status', ['pending', 'processing', 'shipped'])
            ->where('created_at', '>=', $period7Days)
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day_key');

        $claimBacklog7dByDay = WarrantyClaim::query()
            ->selectRaw('DATE(COALESCE(requested_at, created_at)) as day_key, COUNT(*) as total')
            ->whereIn('status', ['submitted', 'reviewing'])
            ->whereRaw('COALESCE(requested_at, created_at) >= ?', [$period7Days])
            ->groupByRaw('DATE(COALESCE(requested_at, created_at))')
            ->pluck('total', 'day_key');

        $backlog7dByDay = $trendDays
            ->mapWithKeys(function ($day) use ($orderBacklog7dByDay, $claimBacklog7dByDay) {
                $dayKey = $day->toDateString();

                return [
                    $dayKey => (int) ($orderBacklog7dByDay->get($dayKey, 0)) + (int) ($claimBacklog7dByDay->get($dayKey, 0)),
                ];
            });

        $trend7d = [
            'revenue' => $this->buildTrendSeries($trendDays, $revenue7dByDay),
            'claims' => $this->buildTrendSeries($trendDays, $claims7dByDay),
            'backlog' => $this->buildTrendSeries($trendDays, $backlog7dByDay),
        ];

        $aiFeedbackSummary = [
            'total_feedback_7d' => 0,
            'helpful_feedback_7d' => 0,
            'not_helpful_feedback_7d' => 0,
            'helpful_rate_7d' => 0.0,
        ];
        $aiFeedbackByIntent = [];
        $aiFeedbackByProvider = [];
        $aiFeedbackByReasonCode = [];
        $aiIntentRootCausePriorities = [];

        $currentFeedbackWindowStart = $period7Days->copy();
        $previousFeedbackWindowEnd = $currentFeedbackWindowStart->copy()->subSecond();
        $previousFeedbackWindowStart = $previousFeedbackWindowEnd->copy()->subDays(6)->startOfDay();

        $aiBenchmarkSummary = [
            'latest_status' => 'missing',
            'latest_pass_rate_percent' => null,
            'latest_threshold_percent' => null,
            'latest_generated_at' => null,
            'latest_generated_label' => null,
            'window_days' => $benchmarkWindowDays,
            'days_with_report' => 0,
            'failed_days' => 0,
            'average_pass_rate' => 0.0,
            'days_with_report_7d' => 0,
            'failed_days_7d' => 0,
            'average_pass_rate_7d' => 0.0,
        ];

        $aiBenchmarkTrend7d = [];

        if (Schema::hasTable('ai_assistant_feedback')) {
            $hasReasonCodeColumn = Schema::hasColumn('ai_assistant_feedback', 'reason_code');
            $hasProviderColumn = Schema::hasColumn('ai_assistant_feedback', 'provider');
            $hasReasonColumn = Schema::hasColumn('ai_assistant_feedback', 'reason');

            $feedbackColumns = ['intent', 'rating', 'metadata'];
            if ($hasReasonColumn) {
                $feedbackColumns[] = 'reason';
            }
            if ($hasReasonCodeColumn) {
                $feedbackColumns[] = 'reason_code';
            }
            if ($hasProviderColumn) {
                $feedbackColumns[] = 'provider';
            }

            $feedbackRows = AiAssistantFeedback::query()
                ->where('created_at', '>=', $period7Days)
                ->get($feedbackColumns);

            $totalFeedbackCount = $feedbackRows->count();
            $helpfulFeedbackCount = $feedbackRows->where('rating', 1)->count();
            $notHelpfulFeedbackCount = $feedbackRows->where('rating', -1)->count();

            $aiFeedbackSummary = [
                'total_feedback_7d' => $totalFeedbackCount,
                'helpful_feedback_7d' => $helpfulFeedbackCount,
                'not_helpful_feedback_7d' => $notHelpfulFeedbackCount,
                'helpful_rate_7d' => $totalFeedbackCount > 0
                    ? round(($helpfulFeedbackCount / $totalFeedbackCount) * 100, 1)
                    : 0.0,
            ];

            $aiFeedbackByIntent = $feedbackRows
                ->groupBy(fn(AiAssistantFeedback $feedback): string => $this->normalizeIntentKey((string) $feedback->intent))
                ->map(fn(Collection $items, string $intent): array => $this->buildFeedbackBreakdownRow($intent, $items))
                ->sortByDesc('total')
                ->values()
                ->all();

            $aiFeedbackByProvider = $feedbackRows
                ->groupBy(function (AiAssistantFeedback $feedback) use ($hasProviderColumn): string {
                    $providerValue = $hasProviderColumn ? (string) $feedback->provider : '';
                    if ($providerValue === '') {
                        $providerValue = (string) data_get($feedback->metadata, 'provider', 'rule_based');
                    }

                    return $this->normalizeProviderKey($providerValue);
                })
                ->map(fn(Collection $items, string $provider): array => $this->buildFeedbackBreakdownRow($provider, $items))
                ->sortByDesc('total')
                ->values()
                ->all();

            $aiFeedbackByReasonCode = $feedbackRows
                ->groupBy(function (AiAssistantFeedback $feedback) use ($hasReasonCodeColumn): string {
                    $reasonCodeValue = $hasReasonCodeColumn ? (string) $feedback->reason_code : '';

                    return $this->normalizeReasonCodeKey($reasonCodeValue, (int) $feedback->rating);
                })
                ->map(function (Collection $items, string $reasonCode): array {
                    $row = $this->buildFeedbackBreakdownRow($reasonCode, $items);
                    $row['display_label'] = $this->resolveReasonCodeDisplayLabel($reasonCode);

                    return $row;
                })
                ->sortByDesc('total')
                ->values()
                ->all();

            $rootCauseColumns = ['intent', 'rating'];
            if ($hasReasonColumn) {
                $rootCauseColumns[] = 'reason';
            }
            if ($hasReasonCodeColumn) {
                $rootCauseColumns[] = 'reason_code';
            }

            $currentNegativeFeedbackRows = AiAssistantFeedback::query()
                ->where('rating', -1)
                ->whereBetween('created_at', [$currentFeedbackWindowStart, $now])
                ->get($rootCauseColumns);

            $previousNegativeFeedbackRows = AiAssistantFeedback::query()
                ->where('rating', -1)
                ->whereBetween('created_at', [$previousFeedbackWindowStart, $previousFeedbackWindowEnd])
                ->get($rootCauseColumns);

            $aiIntentRootCausePriorities = $this->buildIntentRootCausePriorities(
                $currentNegativeFeedbackRows,
                $previousNegativeFeedbackRows,
                $hasReasonCodeColumn,
                $hasReasonColumn
            );
        }

        $benchmarkReportsByDay = $this->loadBenchmarkReportsByDay($benchmarkPeriodStart);

        if ($benchmarkReportsByDay->isNotEmpty()) {
            $latestBenchmarkReport = $benchmarkReportsByDay
                ->sortByDesc('generated_at_timestamp')
                ->first();

            $averagePassRate = (float) ($benchmarkReportsByDay->avg('pass_rate_percent') ?? 0.0);

            $aiBenchmarkSummary = [
                'latest_status' => (string) ($latestBenchmarkReport['status'] ?? 'unknown'),
                'latest_pass_rate_percent' => isset($latestBenchmarkReport['pass_rate_percent'])
                    ? (float) $latestBenchmarkReport['pass_rate_percent']
                    : null,
                'latest_threshold_percent' => isset($latestBenchmarkReport['threshold_percent'])
                    ? (float) $latestBenchmarkReport['threshold_percent']
                    : null,
                'latest_generated_at' => (string) ($latestBenchmarkReport['generated_at'] ?? ''),
                'latest_generated_label' => (string) ($latestBenchmarkReport['generated_label'] ?? ''),
                'window_days' => $benchmarkWindowDays,
                'days_with_report' => $benchmarkReportsByDay->count(),
                'failed_days' => $benchmarkReportsByDay->where('status', 'fail')->count(),
                'average_pass_rate' => round($averagePassRate, 1),
                'days_with_report_7d' => $benchmarkReportsByDay->count(),
                'failed_days_7d' => $benchmarkReportsByDay->where('status', 'fail')->count(),
                'average_pass_rate_7d' => round($averagePassRate, 1),
            ];
        }

        $aiBenchmarkTrend7d = $this->buildBenchmarkTrendSeries(
            $benchmarkTrendDays,
            $benchmarkReportsByDay->keyBy('day_key')
        );

        return view('admin.dashboard', [
            'overview' => $overview,
            'triage' => $triage,
            'metrics' => $metrics,
            'trend7d' => $trend7d,
            'aiFeedbackSummary' => $aiFeedbackSummary,
            'aiFeedbackByIntent' => $aiFeedbackByIntent,
            'aiFeedbackByProvider' => $aiFeedbackByProvider,
            'aiFeedbackByReasonCode' => $aiFeedbackByReasonCode,
            'aiIntentRootCausePriorities' => $aiIntentRootCausePriorities,
            'aiBenchmarkSummary' => $aiBenchmarkSummary,
            'aiBenchmarkTrend7d' => $aiBenchmarkTrend7d,
            'benchmarkWindowDays' => $benchmarkWindowDays,
            'benchmarkWindowOptions' => $benchmarkWindowOptions,
        ]);
    }

    private function normalizeIntentKey(string $intent): string
    {
        $normalizedIntent = trim(strtolower($intent));

        return $normalizedIntent !== '' ? $normalizedIntent : 'unknown';
    }

    private function normalizeProviderKey(string $provider): string
    {
        $normalizedProvider = trim(strtolower($provider));

        return $normalizedProvider !== '' ? $normalizedProvider : 'rule_based';
    }

    private function normalizeReasonCodeKey(string $reasonCode, int $rating): string
    {
        $normalizedReasonCode = strtolower(trim($reasonCode));
        $normalizedReasonCode = str_replace(['-', ' '], '_', $normalizedReasonCode);
        $normalizedReasonCode = preg_replace('/[^a-z0-9_]/', '_', $normalizedReasonCode) ?: '';
        $normalizedReasonCode = preg_replace('/_+/', '_', $normalizedReasonCode) ?: '';
        $normalizedReasonCode = trim($normalizedReasonCode, '_');

        if ($normalizedReasonCode !== '') {
            return $normalizedReasonCode;
        }

        return $rating > 0 ? 'helpful_generic' : 'not_helpful_generic';
    }

    private function resolveReasonCodeDisplayLabel(string $reasonCode): string
    {
        $normalizedReasonCode = $this->normalizeReasonCodeKey($reasonCode, -1);

        $labelMap = [
            'helpful_generic' => 'Jawaban membantu (umum)',
            'helpful_answer_quality' => 'Kualitas jawaban baik',
            'helpful_recommendation_accuracy' => 'Rekomendasi produk tepat',
            'helpful_payment_instruction' => 'Instruksi pembayaran jelas',
            'helpful_order_tracking_clarity' => 'Status pesanan jelas dan runtut',
            'helpful_troubleshooting_steps' => 'Langkah troubleshooting jelas',
            'helpful_checkout_guidance' => 'Panduan checkout jelas',
            'helpful_shipping_info' => 'Informasi pengiriman jelas',
            'helpful_refund_guidance' => 'Panduan refund jelas',
            'helpful_warranty_guidance' => 'Panduan garansi jelas',
            'helpful_context_awareness' => 'Jawaban sesuai konteks pengguna',
            'helpful_policy_explanation' => 'Penjelasan kebijakan jelas',
            'helpful_price_transparency' => 'Informasi harga transparan',
            'helpful_privacy_assurance' => 'Penjelasan privasi meyakinkan',
            'helpful_fast_response' => 'Respon cepat',
            'helpful_speed' => 'Respon cepat',
            'helpful_latency' => 'Waktu respon baik',

            'not_helpful_generic' => 'Jawaban kurang membantu (umum)',
            'not_helpful_answer_quality' => 'Kualitas jawaban kurang baik',
            'not_helpful_recommendation_accuracy' => 'Rekomendasi produk kurang tepat',
            'not_helpful_payment_instruction' => 'Instruksi pembayaran kurang jelas',
            'not_helpful_wrong_intent' => 'Intent terdeteksi kurang tepat',
            'not_helpful_incomplete_answer' => 'Jawaban belum lengkap atau terpotong',
            'not_helpful_hallucination' => 'Informasi kurang akurat',
            'not_helpful_outdated_info' => 'Informasi sudah tidak relevan',
            'not_helpful_no_actionable_step' => 'Tidak ada langkah yang bisa langsung dilakukan',
            'not_helpful_slow_response' => 'Respon terlalu lambat',
            'not_helpful_speed' => 'Respon terlalu lambat',
            'not_helpful_latency' => 'Waktu respon terlalu lama',
            'not_helpful_timeout' => 'Respon timeout atau gagal dimuat',
            'not_helpful_too_short' => 'Jawaban terlalu singkat',
            'not_helpful_too_verbose' => 'Jawaban terlalu panjang dan bertele-tele',
            'not_helpful_repetition' => 'Jawaban berulang',
            'not_helpful_tone' => 'Nada bahasa kurang tepat',
            'not_helpful_language_mismatch' => 'Bahasa tidak sesuai preferensi pengguna',
            'not_helpful_privacy_confusing' => 'Penjelasan privasi membingungkan',
            'not_helpful_policy_conflict' => 'Jawaban bertentangan dengan kebijakan toko',
            'not_helpful_stock_confusion' => 'Informasi stok membingungkan',
            'not_helpful_price_confusion' => 'Informasi harga membingungkan',
            'not_helpful_order_status_confusion' => 'Status pesanan tidak jelas',
            'not_helpful_payment_proof_confusion' => 'Instruksi bukti pembayaran membingungkan',
            'not_helpful_checkout_flow_confusion' => 'Alur checkout tidak jelas',

            'privacy_concern' => 'Kekhawatiran privasi data',
            'privacy_data_safety' => 'Kekhawatiran keamanan data',
            'payment_proof_privacy_concern' => 'Kekhawatiran privasi bukti transfer',
            'security_concern' => 'Kekhawatiran keamanan',
            'trust_concern' => 'Pengguna belum cukup percaya pada jawaban',

            'provider_error' => 'Gangguan provider AI',
            'provider_rate_limited' => 'Provider terkena batas rate limit',
            'provider_billing_issue' => 'Provider terkendala billing',
            'provider_fallback_triggered' => 'Terjadi fallback ke provider cadangan',
            'budget_exhausted' => 'Budget AI harian habis',
            'prompt_build_failed' => 'Penyusunan prompt gagal',

            'neutral_feedback' => 'Feedback netral',
            'unknown' => 'Kode alasan belum terklasifikasi',
            'other' => 'Lainnya',
        ];

        if (array_key_exists($normalizedReasonCode, $labelMap)) {
            return $labelMap[$normalizedReasonCode];
        }

        if (str_starts_with($normalizedReasonCode, 'helpful_')) {
            return 'Membantu: ' . $this->humanizeReasonCodeSuffix(substr($normalizedReasonCode, strlen('helpful_')));
        }

        if (str_starts_with($normalizedReasonCode, 'not_helpful_')) {
            return 'Kurang membantu: ' . $this->humanizeReasonCodeSuffix(substr($normalizedReasonCode, strlen('not_helpful_')));
        }

        if (str_starts_with($normalizedReasonCode, 'privacy_') || str_contains($normalizedReasonCode, 'privacy')) {
            return 'Privasi: ' . $this->humanizeReasonCodeSuffix(str_replace('privacy_', '', $normalizedReasonCode));
        }

        if (str_starts_with($normalizedReasonCode, 'security_') || str_contains($normalizedReasonCode, 'security')) {
            return 'Keamanan: ' . $this->humanizeReasonCodeSuffix(str_replace('security_', '', $normalizedReasonCode));
        }

        return $this->humanizeReasonCodeSuffix($normalizedReasonCode);
    }

    private function humanizeReasonCodeSuffix(string $reasonCodeSuffix): string
    {
        $normalizedSuffix = trim(str_replace('_', ' ', strtolower($reasonCodeSuffix)));

        if ($normalizedSuffix === '') {
            return 'Alasan belum tersedia';
        }

        $titleCased = ucwords($normalizedSuffix);

        $acronymMap = [
            'Ai' => 'AI',
            'Llm' => 'LLM',
            'Qris' => 'QRIS',
            'Cod' => 'COD',
            'Faq' => 'FAQ',
            'Api' => 'API',
            'Ui' => 'UI',
            'Ux' => 'UX',
            'Sla' => 'SLA',
            'Idr' => 'IDR',
        ];

        return str_replace(array_keys($acronymMap), array_values($acronymMap), $titleCased);
    }

    private function buildFeedbackBreakdownRow(string $label, Collection $items): array
    {
        $totalCount = $items->count();
        $helpfulCount = $items->where('rating', 1)->count();
        $notHelpfulCount = $items->where('rating', -1)->count();

        return [
            'label' => $label,
            'total' => $totalCount,
            'helpful' => $helpfulCount,
            'not_helpful' => $notHelpfulCount,
            'helpful_rate' => $totalCount > 0
                ? round(($helpfulCount / $totalCount) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * @param Collection<int, AiAssistantFeedback> $currentNegativeRows
     * @param Collection<int, AiAssistantFeedback> $previousNegativeRows
     * @return array<int, array<string, mixed>>
     */
    private function buildIntentRootCausePriorities(
        Collection $currentNegativeRows,
        Collection $previousNegativeRows,
        bool $hasReasonCodeColumn,
        bool $hasReasonColumn,
    ): array {
        if ($currentNegativeRows->isEmpty()) {
            return [];
        }

        $currentByIntent = $currentNegativeRows
            ->groupBy(fn(AiAssistantFeedback $feedback): string => $this->normalizeIntentKey((string) $feedback->intent));

        $previousByIntent = $previousNegativeRows
            ->groupBy(fn(AiAssistantFeedback $feedback): string => $this->normalizeIntentKey((string) $feedback->intent));

        $priorityRows = [];

        foreach ($currentByIntent as $intent => $currentIntentRows) {
            $currentIntentCount = $currentIntentRows->count();
            if ($currentIntentCount <= 0) {
                continue;
            }

            $currentReasonCounts = $currentIntentRows
                ->groupBy(fn(AiAssistantFeedback $feedback): string => $this->resolveFeedbackReasonCode(
                    $feedback,
                    $hasReasonCodeColumn,
                    $hasReasonColumn
                ))
                ->map(fn(Collection $rows): int => $rows->count());

            if ($currentReasonCounts->isEmpty()) {
                continue;
            }

            $previousIntentRows = $previousByIntent->get($intent);
            $previousReasonCounts = $previousIntentRows instanceof Collection
                ? $previousIntentRows
                ->groupBy(fn(AiAssistantFeedback $feedback): string => $this->resolveFeedbackReasonCode(
                    $feedback,
                    $hasReasonCodeColumn,
                    $hasReasonColumn
                ))
                ->map(fn(Collection $rows): int => $rows->count())
                : collect();

            $bestRow = null;

            foreach ($currentReasonCounts as $reasonCode => $currentReasonCountRaw) {
                $currentReasonCount = (int) $currentReasonCountRaw;
                if ($currentReasonCount <= 0) {
                    continue;
                }

                $previousReasonCount = (int) $previousReasonCounts->get($reasonCode, 0);
                $deltaNegativeCount = $currentReasonCount - $previousReasonCount;
                $negativeSharePercent = round(($currentReasonCount / $currentIntentCount) * 100, 1);

                $severityWeight = $this->resolveReasonSeverityWeight((string) $reasonCode);
                $priorityScore = round(
                    ($currentReasonCount * 2.2)
                        + (max(0, $deltaNegativeCount) * 1.6)
                        + ($severityWeight * 1.4)
                        + ($negativeSharePercent / 25),
                    2
                );

                if (! is_array($bestRow) || $priorityScore > (float) ($bestRow['priority_score'] ?? -1)) {
                    $bestRow = [
                        'intent' => $intent,
                        'intent_label' => strtoupper($intent),
                        'reason_code' => (string) $reasonCode,
                        'reason_display_label' => $this->resolveReasonCodeDisplayLabel((string) $reasonCode),
                        'current_negative_total' => $currentIntentCount,
                        'current_negative_count' => $currentReasonCount,
                        'previous_negative_count' => $previousReasonCount,
                        'delta_negative_count' => $deltaNegativeCount,
                        'delta_negative_percent' => $this->calculateDeltaPercent($currentReasonCount, $previousReasonCount),
                        'negative_share_percent' => $negativeSharePercent,
                        'priority_score' => $priorityScore,
                    ];
                }
            }

            if (! is_array($bestRow)) {
                continue;
            }

            $bestReasonCode = (string) ($bestRow['reason_code'] ?? 'not_helpful_generic');

            $bestRow['severity_level'] = $this->resolvePrioritySeverityLevel((float) ($bestRow['priority_score'] ?? 0));
            $bestRow['recommended_patch'] = $this->resolveIntentRootCausePatchRecommendation($intent, $bestReasonCode);

            $priorityRows[] = $bestRow;
        }

        usort($priorityRows, static function (array $left, array $right): int {
            $scoreCompare = ((float) ($right['priority_score'] ?? 0)) <=> ((float) ($left['priority_score'] ?? 0));
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return ((int) ($right['current_negative_count'] ?? 0)) <=> ((int) ($left['current_negative_count'] ?? 0));
        });

        foreach ($priorityRows as $index => &$priorityRow) {
            $priorityRow['priority_rank'] = $index + 1;
        }
        unset($priorityRow);

        return array_slice($priorityRows, 0, 12);
    }

    private function resolveFeedbackReasonCode(
        AiAssistantFeedback $feedback,
        bool $hasReasonCodeColumn,
        bool $hasReasonColumn,
    ): string {
        $reasonCodeValue = $hasReasonCodeColumn ? (string) $feedback->reason_code : '';
        $normalizedReasonCode = $this->normalizeReasonCodeKey($reasonCodeValue, (int) $feedback->rating);

        if ($normalizedReasonCode !== 'not_helpful_generic') {
            return $normalizedReasonCode;
        }

        if (! $hasReasonColumn) {
            return $normalizedReasonCode;
        }

        $inferredReasonCode = $this->inferReasonCodeFromReasonText((string) $feedback->reason);

        return $inferredReasonCode !== '' ? $inferredReasonCode : $normalizedReasonCode;
    }

    private function inferReasonCodeFromReasonText(string $reasonText): string
    {
        $normalizedReasonText = strtolower(trim($reasonText));

        if ($normalizedReasonText === '') {
            return '';
        }

        $inferenceMap = [
            'not_helpful_payment_instruction' => ['bayar', 'pembayaran', 'transfer', 'qris'],
            'not_helpful_payment_proof_confusion' => ['bukti', 'proof', 'upload'],
            'not_helpful_order_status_confusion' => ['status', 'pesanan', 'order', 'tracking', 'resi'],
            'not_helpful_checkout_flow_confusion' => ['checkout', 'keranjang', 'alamat'],
            'not_helpful_timeout' => ['timeout', 'lambat', 'lama', 'lemot'],
            'not_helpful_no_actionable_step' => ['langkah', 'solusi', 'tidak jelas', 'ga jelas', 'gak jelas'],
            'not_helpful_hallucination' => ['tidak akurat', 'salah info', 'ngarang'],
            'not_helpful_wrong_intent' => ['tidak nyambung', 'salah intent', 'melenceng'],
            'privacy_concern' => ['privasi', 'data aman', 'bocor data', 'takut tersebar'],
            'security_concern' => ['keamanan', 'disalahgunakan', 'hacker'],
        ];

        foreach ($inferenceMap as $reasonCode => $keywords) {
            if ($this->containsAnyKeyword($normalizedReasonText, $keywords)) {
                return $reasonCode;
            }
        }

        return '';
    }

    private function resolveReasonSeverityWeight(string $reasonCode): int
    {
        $normalizedReasonCode = $this->normalizeReasonCodeKey($reasonCode, -1);

        if ($this->containsAnyKeyword($normalizedReasonCode, [
            'privacy',
            'security',
            'provider_error',
            'provider_rate_limited',
            'provider_billing_issue',
            'budget_exhausted',
        ])) {
            return 6;
        }

        if ($this->containsAnyKeyword($normalizedReasonCode, [
            'payment_instruction',
            'payment_proof',
            'order_status_confusion',
            'checkout_flow_confusion',
            'hallucination',
            'wrong_intent',
            'policy_conflict',
        ])) {
            return 5;
        }

        if ($this->containsAnyKeyword($normalizedReasonCode, [
            'incomplete_answer',
            'no_actionable_step',
            'timeout',
            'slow_response',
            'latency',
        ])) {
            return 4;
        }

        if ($this->containsAnyKeyword($normalizedReasonCode, [
            'too_short',
            'too_verbose',
            'repetition',
            'tone',
            'language_mismatch',
        ])) {
            return 3;
        }

        return 2;
    }

    private function resolvePrioritySeverityLevel(float $priorityScore): string
    {
        if ($priorityScore >= 24) {
            return 'critical';
        }

        if ($priorityScore >= 18) {
            return 'high';
        }

        if ($priorityScore >= 12) {
            return 'medium';
        }

        return 'low';
    }

    private function resolveIntentRootCausePatchRecommendation(string $intent, string $reasonCode): string
    {
        $normalizedIntent = $this->normalizeIntentKey($intent);
        $normalizedReasonCode = $this->normalizeReasonCodeKey($reasonCode, -1);

        if ($this->containsAnyKeyword($normalizedReasonCode, ['payment_instruction', 'payment_proof'])) {
            return 'Perjelas playbook pembayaran: format bukti valid, checklist verifikasi, dan fallback langkah jika upload gagal.';
        }

        if ($this->containsAnyKeyword($normalizedReasonCode, ['order_status_confusion', 'checkout_flow_confusion'])) {
            return 'Tambah alur bernomor pada jawaban, sertakan menu UI yang harus dibuka, lalu akhiri dengan konfirmasi langkah berikutnya.';
        }

        if ($this->containsAnyKeyword($normalizedReasonCode, ['privacy', 'security'])) {
            return 'Pertegas penjelasan privasi dan keamanan data secara singkat, lalu berikan opsi aman yang setara untuk pengguna sensitif.';
        }

        if ($this->containsAnyKeyword($normalizedReasonCode, ['timeout', 'slow_response', 'latency', 'provider_error'])) {
            return 'Kurangi verbosity prompt untuk intent ini, aktifkan fallback yang lebih cepat, dan pantau rasio timeout per provider.';
        }

        if ($this->containsAnyKeyword($normalizedReasonCode, ['wrong_intent', 'hallucination'])) {
            return 'Perkuat guard intent dengan contoh counter-case dan tambahkan jawaban aman saat data tidak cukup.';
        }

        return match ($normalizedIntent) {
            'product_recommendation' => 'Refine template rekomendasi agar selalu menanyakan kebutuhan ruang, budget, dan batas daya sebelum memberi opsi produk.',
            'website_help' => 'Perjelas tutorial website dengan urutan langkah yang konsisten, gunakan istilah menu yang sama dengan UI aktual.',
            'order_tracking' => 'Pastikan respons tracking selalu meminta order code saat belum ada, lalu jelaskan status terakhir dan next action.',
            'troubleshooting' => 'Tambahkan decision-tree troubleshooting berbasis akar masalah, mulai dari langkah mitigasi tercepat lalu eskalasi.',
            'emotional_support' => 'Perkuat respons empatik di awal lalu lanjutkan dengan opsi bantuan praktis agar pengguna merasa ditangani.',
            default => 'Review 10 percakapan negatif terbaru pada intent ini, lalu update prompt guard dan contoh jawaban target.',
        };
    }

    private function calculateDeltaPercent(int $currentCount, int $previousCount): ?float
    {
        if ($previousCount <= 0) {
            return null;
        }

        return round((($currentCount - $previousCount) / $previousCount) * 100, 1);
    }

    /**
     * @param array<int, string> $keywords
     */
    private function containsAnyKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function buildTrendSeries(Collection $trendDays, Collection $valuesByDay): array
    {
        $values = $trendDays
            ->map(fn($day) => (int) $valuesByDay->get($day->toDateString(), 0));

        $maxValue = max(1, ...$values->all());

        return $trendDays
            ->values()
            ->map(function ($day, $index) use ($values, $maxValue) {
                $value = (int) $values[$index];
                $scaledHeight = (int) round(($value / $maxValue) * 100);

                return [
                    'date' => $day->toDateString(),
                    'label' => $day->format('d M'),
                    'short_label' => $day->format('D'),
                    'value' => $value,
                    'height' => $value > 0 ? max(14, $scaledHeight) : 6,
                ];
            })
            ->all();
    }

    private function loadBenchmarkReportsByDay(Carbon $periodStart): Collection
    {
        $benchmarkDirectory = storage_path('app/ai-benchmarks');

        if (! File::isDirectory($benchmarkDirectory)) {
            return collect();
        }

        $reportsByDay = [];

        foreach (File::files($benchmarkDirectory) as $benchmarkFile) {
            $fileName = $benchmarkFile->getFilename();
            $normalizedFileName = strtolower($fileName);

            if (! str_starts_with($normalizedFileName, 'ai-benchmark-') || ! str_ends_with($normalizedFileName, '.json')) {
                continue;
            }

            try {
                $fileContents = (string) File::get($benchmarkFile->getPathname());
            } catch (\Throwable) {
                continue;
            }

            $reportPayload = json_decode($fileContents, true);

            if (! is_array($reportPayload)) {
                continue;
            }

            $generatedAt = $this->resolveBenchmarkGeneratedAt($reportPayload, $fileName);

            if (! $generatedAt instanceof Carbon || $generatedAt->lt($periodStart)) {
                continue;
            }

            $dayKey = $generatedAt->toDateString();

            $status = trim(strtolower((string) ($reportPayload['status'] ?? 'unknown')));
            if (! in_array($status, ['pass', 'fail'], true)) {
                $status = 'unknown';
            }

            $passRatePercent = max(0.0, min(100.0, (float) data_get($reportPayload, 'summary.pass_rate_percent', 0)));
            $thresholdRaw = data_get($reportPayload, 'summary.threshold_percent');

            $thresholdPercent = is_numeric($thresholdRaw)
                ? max(0.0, min(100.0, (float) $thresholdRaw))
                : null;

            $candidateReport = [
                'day_key' => $dayKey,
                'status' => $status,
                'pass_rate_percent' => round($passRatePercent, 1),
                'threshold_percent' => $thresholdPercent !== null ? round($thresholdPercent, 1) : null,
                'generated_at' => $generatedAt->toISOString(),
                'generated_label' => $generatedAt->format('d M Y H:i'),
                'generated_at_timestamp' => $generatedAt->timestamp,
            ];

            $existingReport = $reportsByDay[$dayKey] ?? null;

            if (! is_array($existingReport) || (int) $candidateReport['generated_at_timestamp'] >= (int) ($existingReport['generated_at_timestamp'] ?? 0)) {
                $reportsByDay[$dayKey] = $candidateReport;
            }
        }

        return collect(array_values($reportsByDay))
            ->sortBy('day_key')
            ->values();
    }

    private function resolveBenchmarkGeneratedAt(array $reportPayload, string $fileName): ?Carbon
    {
        $candidateValues = [
            data_get($reportPayload, 'generated_at'),
            data_get($reportPayload, 'summary.finished_at'),
            data_get($reportPayload, 'summary.started_at'),
        ];

        foreach ($candidateValues as $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                continue;
            }
        }

        if (preg_match('/ai-benchmark-(\d{8})-(\d{6})\.json$/', $fileName, $matches) === 1) {
            try {
                return Carbon::createFromFormat('Ymd-His', $matches[1] . '-' . $matches[2]);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function buildBenchmarkTrendSeries(Collection $trendDays, Collection $reportsByDay): array
    {
        return $trendDays
            ->values()
            ->map(function ($day) use ($reportsByDay) {
                $dayKey = $day->toDateString();
                $report = $reportsByDay->get($dayKey);
                $hasReport = is_array($report);

                $passRatePercent = $hasReport
                    ? (float) ($report['pass_rate_percent'] ?? 0.0)
                    : 0.0;

                $status = $hasReport
                    ? (string) ($report['status'] ?? 'unknown')
                    : 'missing';

                return [
                    'date' => $dayKey,
                    'label' => $day->format('d M'),
                    'short_label' => $day->format('D'),
                    'value' => $hasReport ? round($passRatePercent, 1) : null,
                    'height' => $hasReport ? max(10, (int) round($passRatePercent)) : 6,
                    'status' => $status,
                    'has_report' => $hasReport,
                    'threshold_percent' => $hasReport && isset($report['threshold_percent'])
                        ? (float) $report['threshold_percent']
                        : null,
                ];
            })
            ->all();
    }
}
