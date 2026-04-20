<?php

namespace Tests\Feature;

use App\Models\AiAssistantFeedback;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AiWeeklyQualityReportCommandTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_ai_weekly_report_command_generates_json_and_text_artifacts(): void
    {
        $asOfDate = Carbon::parse('2026-04-20 10:00:00');
        $outputDirectory = storage_path('app/testing/ai-weekly-reports');

        File::deleteDirectory($outputDirectory);

        $currentStart = $asOfDate->copy()->subDays(6)->startOfDay();
        $previousStart = $currentStart->copy()->subDays(7)->startOfDay();

        $this->createFeedbackBatch(6, [
            'created_at' => $currentStart->copy()->addDay()->setHour(10),
            'rating' => -1,
            'reason_code' => 'not_helpful_wrong_intent',
            'reason' => 'Intent jawaban meleset dari pertanyaan user.',
            'llm_status' => 'primary_success',
            'fallback_used' => false,
            'response_latency_ms' => 1800,
        ]);

        $this->createFeedbackBatch(4, [
            'created_at' => $currentStart->copy()->addDays(2)->setHour(12),
            'rating' => -1,
            'reason_code' => 'not-helpful-payment_instruction',
            'reason' => 'Langkah pembayaran kurang jelas.',
            'llm_status' => 'fallback_failed',
            'fallback_used' => true,
            'response_latency_ms' => 11000,
        ]);

        $this->createFeedbackBatch(2, [
            'created_at' => $currentStart->copy()->addDays(3)->setHour(8),
            'rating' => -1,
            'reason_code' => '',
            'reason' => 'Respon lama banget jadi tidak membantu.',
            'llm_status' => 'primary_success',
            'fallback_used' => false,
            'response_latency_ms' => 9200,
        ]);

        $this->createFeedbackBatch(5, [
            'created_at' => $currentStart->copy()->addDays(4)->setHour(15),
            'rating' => 1,
            'reason_code' => 'helpful_generic',
            'reason' => 'Membantu.',
            'llm_status' => 'primary_success',
            'fallback_used' => false,
            'response_latency_ms' => 1200,
        ]);

        $this->createFeedbackBatch(2, [
            'created_at' => $previousStart->copy()->addDay()->setHour(10),
            'rating' => -1,
            'reason_code' => 'not_helpful_wrong_intent',
            'reason' => 'Intent meleset.',
            'llm_status' => 'primary_success',
            'fallback_used' => false,
            'response_latency_ms' => 1700,
        ]);

        $this->createFeedbackBatch(6, [
            'created_at' => $previousStart->copy()->addDays(2)->setHour(11),
            'rating' => -1,
            'reason_code' => 'not_helpful_payment_instruction',
            'reason' => 'Instruksi pembayaran membingungkan.',
            'llm_status' => 'primary_failed',
            'fallback_used' => true,
            'response_latency_ms' => 10000,
        ]);

        $this->createFeedbackBatch(1, [
            'created_at' => $previousStart->copy()->addDays(3)->setHour(8),
            'rating' => -1,
            'reason_code' => 'not_helpful_slow_response',
            'reason' => 'Jawaban lambat.',
            'llm_status' => 'primary_success',
            'fallback_used' => false,
            'response_latency_ms' => 8800,
        ]);

        $this->createFeedbackBatch(7, [
            'created_at' => $previousStart->copy()->addDays(4)->setHour(15),
            'rating' => 1,
            'reason_code' => 'helpful_generic',
            'reason' => 'Membantu.',
            'llm_status' => 'primary_success',
            'fallback_used' => false,
            'response_latency_ms' => 1400,
        ]);

        $this->artisan('ai:report-weekly', [
            '--as-of' => $asOfDate->toDateString(),
            '--days' => '7',
            '--output-dir' => $outputDirectory,
        ])->assertExitCode(0);

        $jsonPath = $outputDirectory . DIRECTORY_SEPARATOR . 'ai-weekly-report-20260420-d7.json';
        $summaryPath = $outputDirectory . DIRECTORY_SEPARATOR . 'ai-weekly-summary-20260420-d7.txt';

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($summaryPath);

        $report = json_decode((string) file_get_contents($jsonPath), true);
        $this->assertIsArray($report);
        $this->assertSame('ai-weekly-quality-report-v1', data_get($report, 'version'));
        $this->assertSame('warning', data_get($report, 'status'));

        $this->assertSame(12, data_get($report, 'summary.total_negative_current'));
        $this->assertSame(9, data_get($report, 'summary.total_negative_previous'));
        $this->assertSame(3, data_get($report, 'summary.negative_delta_count'));

        $topRows = collect(data_get($report, 'top_negative_reason_codes', []));

        $wrongIntentRow = $this->findReasonRow($topRows, 'not_helpful_wrong_intent');
        $this->assertNotNull($wrongIntentRow);
        $this->assertSame(6, data_get($wrongIntentRow, 'current_count'));
        $this->assertSame(2, data_get($wrongIntentRow, 'previous_count'));
        $this->assertSame(4, data_get($wrongIntentRow, 'delta_count'));

        $paymentInstructionRow = $this->findReasonRow($topRows, 'not_helpful_payment_instruction');
        $this->assertNotNull($paymentInstructionRow);
        $this->assertSame(4, data_get($paymentInstructionRow, 'current_count'));
        $this->assertSame(6, data_get($paymentInstructionRow, 'previous_count'));
        $this->assertSame(-2, data_get($paymentInstructionRow, 'delta_count'));

        $this->assertNotEmpty(data_get($report, 'recommendations'));
        $this->assertNotNull($this->findRecommendation($report, 'intent_router_tuning'));
        $this->assertNotNull($this->findRecommendation($report, 'weekly_stabilization_sprint'));

        $summaryText = (string) file_get_contents($summaryPath);
        $this->assertStringContainsString('Top 10 reason_code negatif', $summaryText);
        $this->assertStringContainsString('Rekomendasi aksi prioritas otomatis', $summaryText);
    }

    public function test_ai_weekly_report_command_returns_validation_error_for_invalid_as_of(): void
    {
        $this->artisan('ai:report-weekly', [
            '--as-of' => 'tanggal-ngaco',
            '--days' => '7',
        ])->assertExitCode(1);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createFeedbackBatch(int $count, array $attributes): void
    {
        for ($index = 0; $index < $count; $index++) {
            $this->sequence++;

            $createdAt = Carbon::parse((string) ($attributes['created_at'] ?? now()));
            $rating = (int) ($attributes['rating'] ?? -1);

            AiAssistantFeedback::query()->forceCreate([
                'session_id' => 'session-' . $this->sequence,
                'message_id' => 'message-' . $this->sequence,
                'intent' => (string) ($attributes['intent'] ?? 'website_help'),
                'intent_detected' => (string) ($attributes['intent_detected'] ?? 'website_help'),
                'intent_resolved' => (string) ($attributes['intent_resolved'] ?? 'website_help'),
                'rating' => $rating,
                'reason' => (string) ($attributes['reason'] ?? ''),
                'reason_code' => (string) ($attributes['reason_code'] ?? ''),
                'provider' => (string) ($attributes['provider'] ?? 'rule_based'),
                'model' => (string) ($attributes['model'] ?? 'rule_based_v1'),
                'llm_status' => (string) ($attributes['llm_status'] ?? 'primary_success'),
                'fallback_used' => (bool) ($attributes['fallback_used'] ?? false),
                'response_latency_ms' => (int) ($attributes['response_latency_ms'] ?? 1500),
                'response_source' => (string) ($attributes['response_source'] ?? 'tool'),
                'feedback_version' => (int) ($attributes['feedback_version'] ?? 2),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    private function findReasonRow(Collection $rows, string $reasonCode): ?array
    {
        $row = $rows->first(fn($item): bool => data_get($item, 'reason_code') === $reasonCode);

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function findRecommendation(array $report, string $actionKey): ?array
    {
        $recommendations = collect(data_get($report, 'recommendations', []));
        $recommendation = $recommendations->first(fn($item): bool => data_get($item, 'action_key') === $actionKey);

        return is_array($recommendation) ? $recommendation : null;
    }
}
