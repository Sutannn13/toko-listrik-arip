<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AiOfflineEvaluationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_offline_evaluation_command_generates_json_report(): void
    {
        $outputPath = storage_path('app/testing/ai-benchmark-pass.json');
        File::delete($outputPath);

        config()->set('services.ai.provider', 'rule_based');
        config()->set('ai_benchmark.cases', [
            [
                'id' => 'website_help_shipping',
                'message' => 'Ongkir berapa untuk checkout?',
                'expected_intent' => 'website_help',
            ],
        ]);

        $this->artisan('ai:evaluate-offline', [
            '--provider' => 'rule_based',
            '--output' => $outputPath,
            '--fail-below' => '0',
        ])
            ->assertExitCode(0);

        $this->assertFileExists($outputPath);

        $reportJson = file_get_contents($outputPath);
        $this->assertIsString($reportJson);

        $report = json_decode($reportJson, true);
        $this->assertIsArray($report);
        $this->assertSame('ai-benchmark-v1', $report['version']);
        $this->assertSame($outputPath, data_get($report, 'artifacts.0.path'));
        $this->assertSame('pass', data_get($report, 'status'));
    }

    public function test_ai_offline_evaluation_command_returns_gate_failure_exit_code(): void
    {
        $outputPath = storage_path('app/testing/ai-benchmark-fail.json');
        File::delete($outputPath);

        config()->set('services.ai.provider', 'rule_based');
        config()->set('ai_benchmark.cases', [
            [
                'id' => 'force_fail',
                'message' => 'Ongkir berapa untuk checkout?',
                'expected_intent' => 'off_topic',
            ],
        ]);

        $this->artisan('ai:evaluate-offline', [
            '--provider' => 'rule_based',
            '--output' => $outputPath,
            '--fail-below' => '100',
        ])
            ->assertExitCode(2);

        $report = json_decode((string) file_get_contents($outputPath), true);
        $this->assertSame('fail', data_get($report, 'status'));
    }

    public function test_ai_offline_evaluation_command_supports_hard_case_profile(): void
    {
        $outputPath = storage_path('app/testing/ai-benchmark-hard-case.json');
        File::delete($outputPath);

        config()->set('services.ai.provider', 'rule_based');
        config()->set('ai_benchmark.profiles', [
            'hard_case' => [
                'pass_threshold_percent' => 10,
                'cases' => [
                    [
                        'id' => 'hard_case_smoke',
                        'message' => 'Cara checkout pakai COD gimana?',
                        'expected_intent' => 'website_help',
                    ],
                ],
            ],
        ]);

        $this->artisan('ai:evaluate-offline', [
            '--provider' => 'rule_based',
            '--profile' => 'hard_case',
            '--output' => $outputPath,
        ])->assertExitCode(0);

        $report = json_decode((string) file_get_contents($outputPath), true);

        $this->assertSame('hard_case', data_get($report, 'profile'));
        $this->assertSame('hard_case', data_get($report, 'summary.benchmark_profile'));
    }

    public function test_ai_offline_evaluation_command_rejects_unknown_profile(): void
    {
        $this->artisan('ai:evaluate-offline', [
            '--profile' => 'unknown_profile',
        ])->assertExitCode(1);
    }
}
