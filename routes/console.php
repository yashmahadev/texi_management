<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckDelayedDutiesJob;
use App\Jobs\MarkMissingDutiesJob;
use App\Jobs\CheckExpiryNotificationsJob;
use App\Jobs\CreateRecurringDutiesJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Automated Jobs
Schedule::job(new CheckDelayedDutiesJob)->everyMinute();
Schedule::job(new MarkMissingDutiesJob)->everyMinute();

// Run once daily at 8 AM — check DL, PUC, insurance expiries
Schedule::job(new CheckExpiryNotificationsJob)->dailyAt('08:00');

// Run on last day of every month at 11 PM — create next month's recurring duties
Schedule::job(new CreateRecurringDutiesJob)->lastDayOfMonth('23:00');
