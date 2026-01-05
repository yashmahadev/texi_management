<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckDelayedDutiesJob;
use App\Jobs\MarkMissingDutiesJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Automated Jobs
Schedule::job(new CheckDelayedDutiesJob)->everyMinute();
Schedule::job(new MarkMissingDutiesJob)->everyMinute();
