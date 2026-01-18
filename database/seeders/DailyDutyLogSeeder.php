<?php

namespace Database\Seeders;

use App\Models\DailyDutyLog;
use App\Models\MonthlyDuty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DailyDutyLogSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DailyDutyLog::truncate();
        Schema::enableForeignKeyConstraints();

        $monthlyDuties = MonthlyDuty::all();

        foreach ($monthlyDuties as $duty) {
            $currentDate = $duty->start_date;
            $startKm = rand(10000, 50000);

            // Create logs for the first 10 days of the month
            for ($i = 0; $i < 10; $i++) {
                $endKm = $startKm + rand(50, 200);
                
                DailyDutyLog::create([
                    'monthly_duty_id' => $duty->id,
                    'duty_date' => $currentDate->copy()->addDays($i),
                    'start_time' => '09:00:00',
                    'end_time' => '18:00:00',
                    'start_km' => $startKm,
                    'end_km' => $endKm,
                    'total_km' => $endKm - $startKm,
                    'status' => 'completed',
                ]);

                $startKm = $endKm + rand(0, 10); // Some idle KM
            }
        }
    }
}
