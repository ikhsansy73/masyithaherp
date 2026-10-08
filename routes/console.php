<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('accounting:verify-balance')->daily();

// Doc 04 §2: runs on the 20th for the next month's SPP batches.
Schedule::command('billing:generate-invoices')->monthlyOn(20, '01:00');
