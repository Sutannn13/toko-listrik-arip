<?php

namespace App\Console\Commands;

use App\Services\Ai\AiPromptLearningService;
use Illuminate\Console\Command;

class LearnAiPromptRulesFromFeedback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:learn-feedback-rules {--days=30 : Jumlah hari data feedback negatif yang dianalisis} {--min-signals=3 : Minimal jumlah sinyal untuk mengaktifkan rule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bangun rule adaptif prompt AI dari feedback negatif secara terukur';

    /**
     * Execute the console command.
     */
    public function handle(AiPromptLearningService $promptLearning): int
    {
        $summary = $promptLearning->rebuildRulesFromNegativeFeedback(
            (int) $this->option('days'),
            (int) $this->option('min-signals'),
        );

        $this->info('Auto-learning prompt rules selesai dijalankan.');
        $this->line('Window (hari): ' . $summary['window_days']);
        $this->line('Minimum signal count: ' . $summary['minimum_signal_count']);
        $this->line('Total feedback negatif dianalisis: ' . $summary['feedback_samples']);
        $this->line('Rule aktif yang diupdate: ' . $summary['updated_rules']);

        $activeRuleKeys = (array) ($summary['active_rule_keys'] ?? []);
        if (count($activeRuleKeys) > 0) {
            $this->line('Rule keys aktif: ' . implode(', ', $activeRuleKeys));
        }

        return Command::SUCCESS;
    }
}
