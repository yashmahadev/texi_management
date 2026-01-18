<?php

namespace Database\Seeders;

use App\Models\BillingLog;
use App\Models\DailyDutyLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BillingLogSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        BillingLog::truncate();
        Schema::enableForeignKeyConstraints();

        $logs = DailyDutyLog::where('status', 'completed')->take(10)->get();

        foreach ($logs as $log) {
            BillingLog::create([
                'daily_duty_log_id' => $log->id,
                'start_time' => $log->start_time,
                'end_time' => $log->end_time,
                'start_km' => $log->start_km,
                'end_km' => $log->end_km,
                'total_km' => $log->total_km,
                'status' => 'verified',
                'created_by' => 1,
            ]);
        }
    }
}
