<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Jalankan tiap 5 menit agar order pending ter-cancel mendekati SLA 1 jam.
Schedule::command('orders:cancel-unpaid')->everyFiveMinutes();

// Jalankan tiap 30 menit agar prompt AI lebih cepat adaptif terhadap pola feedback negatif terbaru.
Schedule::command('ai:learn-feedback-rules --days=45 --min-signals=2')->everyThirtyMinutes();

// Jalankan evaluasi offline harian dengan hard-case profile sebagai quality gate non-produksi.
Schedule::command('ai:evaluate-offline --provider=rule_based --profile=hard_case')->dailyAt('02:15');

// Jalankan auto-report mingguan untuk memonitor root cause feedback negatif dan prioritas aksi.
Schedule::command('ai:report-weekly --days=7')->weeklyOn(1, '03:05');
