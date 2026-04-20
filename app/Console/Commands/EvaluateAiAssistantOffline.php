<?php

namespace App\Console\Commands;

use App\Services\Ai\AiAssistantOrchestratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class EvaluateAiAssistantOffline extends Command
{
    protected $signature = 'ai:evaluate-offline
        {--provider=rule_based : Provider mode: rule_based atau current}
        {--profile=default : Benchmark profile: default atau hard_case}
        {--output= : Path file laporan JSON}
        {--fail-below= : Minimal pass rate (%) agar gate lulus}';

    protected $description = 'Jalankan benchmark offline AI assistant dan hasilkan laporan JSON untuk evaluasi harian';

    public function handle(AiAssistantOrchestratorService $orchestrator): int
    {
        $profileOption = trim(strtolower((string) $this->option('profile')));
        $requestedProfile = str_replace('-', '_', $profileOption !== '' ? $profileOption : 'default');

        $profileConfig = $this->resolveBenchmarkProfileConfig($requestedProfile);

        if ($profileConfig === null) {
            $availableProfiles = implode(', ', $this->availableProfileNames());

            $this->error('Profile benchmark tidak valid. Gunakan: ' . $availableProfiles . '.');

            return 1;
        }

        $resolvedProfile = (string) ($profileConfig['name'] ?? 'default');
        $cases = is_array($profileConfig['cases'] ?? null)
            ? $profileConfig['cases']
            : [];
        $thresholdFromProfile = (float) ($profileConfig['pass_threshold_percent'] ?? config('ai_benchmark.pass_threshold_percent', 85));

        if (! is_array($cases) || count($cases) === 0) {
            $this->error('Benchmark case kosong untuk profile [' . $resolvedProfile . ']. Isi config/ai_benchmark.php terlebih dahulu.');

            return 1;
        }

        $providerMode = strtolower(trim((string) $this->option('provider')));
        $originalProvider = config('services.ai.provider');

        if (! in_array($providerMode, ['rule_based', 'current'], true)) {
            $this->error('Nilai --provider tidak valid. Gunakan rule_based atau current.');

            return 1;
        }

        if ($providerMode === 'rule_based') {
            config()->set('services.ai.provider', 'rule_based');
        }

        $startedAt = now();
        $startedAtMicrotime = microtime(true);
        $results = [];

        try {
            foreach ($cases as $index => $case) {
                if (! is_array($case)) {
                    $results[] = [
                        'index' => $index,
                        'id' => 'case_' . $index,
                        'passed' => false,
                        'error' => 'Invalid benchmark case format.',
                    ];

                    continue;
                }

                $caseId = trim((string) ($case['id'] ?? ('case_' . $index)));
                $message = trim((string) ($case['message'] ?? ''));
                $expectedIntent = trim((string) ($case['expected_intent'] ?? ''));
                $mustContain = is_array($case['must_contain'] ?? null) ? $case['must_contain'] : [];
                $mustNotContain = is_array($case['must_not_contain'] ?? null) ? $case['must_not_contain'] : [];
                $payloadOverrides = is_array($case['payload'] ?? null) ? $case['payload'] : [];

                if ($message === '') {
                    $results[] = [
                        'index' => $index,
                        'id' => $caseId,
                        'passed' => false,
                        'error' => 'Message benchmark kosong.',
                    ];

                    continue;
                }

                $payload = array_merge([
                    'session_id' => 'bench-' . $caseId,
                    'message' => $message,
                    'context' => [
                        'locale' => 'id',
                        'channel' => 'offline_benchmark',
                    ],
                ], $payloadOverrides);

                $actualIntent = '';
                $replyText = '';
                $intentPassed = false;
                $containsPassed = true;
                $notContainsPassed = true;
                $failureReasons = [];

                try {
                    $response = $orchestrator->respond($payload, null);

                    $actualIntent = trim((string) ($response['intent'] ?? ''));
                    $replyText = trim((string) ($response['reply'] ?? ''));
                    $normalizedReply = mb_strtolower($replyText);

                    $intentPassed = $expectedIntent === '' || $actualIntent === $expectedIntent;

                    if (! $intentPassed) {
                        $failureReasons[] = 'intent_mismatch';
                    }

                    foreach ($mustContain as $phrase) {
                        $normalizedPhrase = mb_strtolower(trim((string) $phrase));
                        if ($normalizedPhrase === '') {
                            continue;
                        }

                        if (! str_contains($normalizedReply, $normalizedPhrase)) {
                            $containsPassed = false;
                            $failureReasons[] = 'missing_phrase:' . $normalizedPhrase;
                        }
                    }

                    foreach ($mustNotContain as $phrase) {
                        $normalizedPhrase = mb_strtolower(trim((string) $phrase));
                        if ($normalizedPhrase === '') {
                            continue;
                        }

                        if (str_contains($normalizedReply, $normalizedPhrase)) {
                            $notContainsPassed = false;
                            $failureReasons[] = 'forbidden_phrase:' . $normalizedPhrase;
                        }
                    }
                } catch (Throwable $exception) {
                    $containsPassed = false;
                    $notContainsPassed = false;
                    $failureReasons[] = 'runtime_exception';

                    $results[] = [
                        'index' => $index,
                        'id' => $caseId,
                        'message' => $message,
                        'expected_intent' => $expectedIntent,
                        'actual_intent' => null,
                        'passed' => false,
                        'intent_passed' => false,
                        'contains_passed' => false,
                        'forbidden_passed' => false,
                        'failure_reasons' => $failureReasons,
                        'error' => $exception->getMessage(),
                    ];

                    continue;
                }

                $passed = $intentPassed && $containsPassed && $notContainsPassed;

                $results[] = [
                    'index' => $index,
                    'id' => $caseId,
                    'message' => $message,
                    'expected_intent' => $expectedIntent,
                    'actual_intent' => $actualIntent,
                    'passed' => $passed,
                    'intent_passed' => $intentPassed,
                    'contains_passed' => $containsPassed,
                    'forbidden_passed' => $notContainsPassed,
                    'failure_reasons' => $failureReasons,
                    'reply_preview' => mb_strimwidth($replyText, 0, 220, '...'),
                ];
            }
        } finally {
            config()->set('services.ai.provider', $originalProvider);
        }

        $totalCases = count($results);
        $passedCases = count(array_filter($results, static fn(array $result): bool => ($result['passed'] ?? false) === true));
        $failedCases = $totalCases - $passedCases;
        $passRate = $totalCases > 0 ? round(($passedCases / $totalCases) * 100, 2) : 0.0;

        $thresholdFromConfig = $thresholdFromProfile;
        $threshold = $this->option('fail-below') !== null
            ? (float) $this->option('fail-below')
            : $thresholdFromConfig;
        $threshold = max(0.0, min(100.0, $threshold));

        $durationMs = (int) round((microtime(true) - $startedAtMicrotime) * 1000);
        $status = $passRate >= $threshold ? 'pass' : 'fail';

        $report = [
            'version' => 'ai-benchmark-v1',
            'profile' => $resolvedProfile,
            'generated_at' => now()->toISOString(),
            'status' => $status,
            'summary' => [
                'total_cases' => $totalCases,
                'passed_cases' => $passedCases,
                'failed_cases' => $failedCases,
                'pass_rate_percent' => $passRate,
                'threshold_percent' => $threshold,
                'benchmark_profile' => $resolvedProfile,
                'duration_ms' => $durationMs,
                'provider_mode' => $providerMode,
                'provider_runtime' => (string) config('services.ai.provider', 'rule_based'),
                'started_at' => $startedAt->toISOString(),
                'finished_at' => now()->toISOString(),
            ],
            'errors' => $this->collectErrorCodes($results),
            'artifacts' => [
                [
                    'type' => 'json_report',
                    'path' => '',
                ],
            ],
            'results' => $results,
        ];

        $outputPath = $this->resolveOutputPath($resolvedProfile);
        $report['artifacts'][0]['path'] = $outputPath;

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->line('Benchmark offline AI selesai dijalankan.');
        $this->line('Provider mode: ' . $providerMode);
        $this->line('Benchmark profile: ' . $resolvedProfile);
        $this->line('Pass rate: ' . number_format($passRate, 2) . '% (threshold ' . number_format($threshold, 2) . '%)');
        $this->line('Laporan JSON: ' . $outputPath);

        $this->table(
            ['Case', 'Expected', 'Actual', 'Pass'],
            array_map(static function (array $result): array {
                return [
                    (string) ($result['id'] ?? '-'),
                    (string) ($result['expected_intent'] ?? '-'),
                    (string) ($result['actual_intent'] ?? '-'),
                    ($result['passed'] ?? false) ? 'yes' : 'no',
                ];
            }, $results)
        );

        if ($status === 'fail') {
            $this->error('Benchmark gate gagal: pass rate di bawah threshold.');

            return 2;
        }

        $this->info('Benchmark gate lulus.');

        return 0;
    }

    private function resolveOutputPath(string $profile): string
    {
        $configuredPath = trim((string) $this->option('output'));

        if ($configuredPath !== '') {
            return $configuredPath;
        }

        $normalizedProfile = trim(strtolower($profile));
        $profileSuffix = $normalizedProfile !== '' && $normalizedProfile !== 'default'
            ? '-' . preg_replace('/[^a-z0-9_\-]/', '', $normalizedProfile)
            : '';

        $fileName = 'ai-benchmark' . $profileSuffix . '-' . now()->format('Ymd-His') . '.json';

        return storage_path('app/ai-benchmarks/' . $fileName);
    }

    /**
     * @return array{name: string, cases: array<int, mixed>, pass_threshold_percent: float}|null
     */
    private function resolveBenchmarkProfileConfig(string $profile): ?array
    {
        $normalizedProfile = trim(strtolower($profile));

        if ($normalizedProfile === '' || $normalizedProfile === 'default') {
            return [
                'name' => 'default',
                'cases' => is_array(config('ai_benchmark.cases')) ? config('ai_benchmark.cases') : [],
                'pass_threshold_percent' => (float) config('ai_benchmark.pass_threshold_percent', 85),
            ];
        }

        $profileConfig = config('ai_benchmark.profiles.' . $normalizedProfile);

        if (! is_array($profileConfig)) {
            return null;
        }

        $cases = is_array($profileConfig['cases'] ?? null)
            ? $profileConfig['cases']
            : [];

        $thresholdRaw = $profileConfig['pass_threshold_percent'] ?? config('ai_benchmark.pass_threshold_percent', 85);
        $threshold = is_numeric($thresholdRaw)
            ? (float) $thresholdRaw
            : (float) config('ai_benchmark.pass_threshold_percent', 85);

        return [
            'name' => $normalizedProfile,
            'cases' => $cases,
            'pass_threshold_percent' => $threshold,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availableProfileNames(): array
    {
        $profiles = config('ai_benchmark.profiles', []);

        $profileNames = ['default'];

        if (is_array($profiles)) {
            foreach (array_keys($profiles) as $profileName) {
                $normalizedName = trim((string) $profileName);

                if ($normalizedName === '') {
                    continue;
                }

                $profileNames[] = $normalizedName;
            }
        }

        $profileNames = array_values(array_unique($profileNames));
        sort($profileNames);

        return $profileNames;
    }

    /**
     * @param array<int, array<string, mixed>> $results
     * @return array<int, array{code: string, count: int}>
     */
    private function collectErrorCodes(array $results): array
    {
        $codes = [];

        foreach ($results as $result) {
            $failureReasons = is_array($result['failure_reasons'] ?? null)
                ? $result['failure_reasons']
                : [];

            foreach ($failureReasons as $reason) {
                $normalizedReason = trim((string) $reason);
                if ($normalizedReason === '') {
                    continue;
                }

                $codes[$normalizedReason] = ($codes[$normalizedReason] ?? 0) + 1;
            }
        }

        $payload = [];
        foreach ($codes as $code => $count) {
            $payload[] = [
                'code' => $code,
                'count' => $count,
            ];
        }

        return $payload;
    }
}
