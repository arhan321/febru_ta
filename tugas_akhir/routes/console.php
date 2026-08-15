<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('dw:sync-and-reconcile')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(
        storage_path('logs/dw-sync-and-reconcile.log')
    );