<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Jalankan tiap 5 menit agar order pending ter-cancel mendekati SLA 1 jam.
Schedule::command('orders:cancel-unpaid')->everyFiveMinutes();
