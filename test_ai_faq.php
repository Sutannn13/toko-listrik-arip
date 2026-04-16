<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orchestrator = app(\App\Services\Ai\AiAssistantOrchestratorService::class);

$tests = [
    'kalau saya pesan cod nanti paketnya akan diantar atau gimana?',
    'alamatnya toko ini dimana? apakah ada link gmaps?',
    'apakah website ini punya nomor whatsapp? saya urgent',
    'saya bingung lampu yang cocok untuk ruangan tengah budget 70k',
    'jam buka toko kapan?',
];

foreach ($tests as $msg) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Q: {$msg}\n";
    echo str_repeat('-', 60) . "\n";

    $result = $orchestrator->respond([
        'message' => $msg,
        'session_id' => 'test-session-001',
    ], null);

    echo "Intent: {$result['intent']}\n";
    echo "Reply:\n{$result['reply']}\n";

    $llm = $result['data']['llm'] ?? null;
    if ($llm) {
        echo "\nLLM: {$llm['provider']} / {$llm['model']} ({$llm['status']})\n";
    } else {
        echo "\nLLM: rule-based only\n";
    }
}

echo "\n✅ Full pipeline test done!\n";
