<?php

namespace App\Jobs;

use App\Models\DailyDutyLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkMissingDutiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Mark pending logs from yesterday (or older) as missing
        DailyDutyLog::where('status', 'pending')
            ->whereDate('duty_date', '<', now())
            ->update(['status' => 'missing']);
    }
}
