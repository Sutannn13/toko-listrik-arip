<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Jalankan tiap 5 menit agar order pending ter-cancel mendekati SLA 1 jam.
Schedule::command('orders:cancel-unpaid')->everyFiveMinutes();

// Jalankan tiap jam agar prompt AI adaptif terhadap pola feedback negatif terbaru.
Schedule::command('ai:learn-feedback-rules --days=30 --min-signals=3')->hourly();
