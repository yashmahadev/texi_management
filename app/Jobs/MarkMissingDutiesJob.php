<?php

namespace App\Jobs;

use App\Models\DailyDutyLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MarkMissingDutiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Checking for missing duties...');
        // Mark pending logs from yesterday (or older) as missing
        DailyDutyLog::where('status', 'pending')
            ->whereDate('duty_date', '<', now())
            ->update(['status' => 'missing']);
        Log::info('End of Checking for missing duties...');
    }
}
