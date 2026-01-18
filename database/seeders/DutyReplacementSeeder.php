<?php

namespace Database\Seeders;

use App\Models\DutyReplacement;
use App\Models\DailyDutyLog;
use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DutyReplacementSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DutyReplacement::truncate();
        Schema::enableForeignKeyConstraints();

        $logs = DailyDutyLog::take(10)->get();
        $drivers = Driver::all();

        foreach ($logs as $log) {
            // Create a replacement for some logs
            if (rand(0, 1)) {
                $originalDriverId = $log->monthlyDuty->primary_driver_id;
                $replacementDriver = $drivers->where('id', '!=', $originalDriverId)->random();

                DutyReplacement::create([
                    'daily_duty_log_id' => $log->id,
                    'original_driver_id' => $originalDriverId,
                    'replacement_driver_id' => $replacementDriver->id,
                    'reason' => 'Original driver on leave',
                    'assigned_by' => 1,
                    'assigned_at' => now(),
                ]);
            }
        }
    }
}
