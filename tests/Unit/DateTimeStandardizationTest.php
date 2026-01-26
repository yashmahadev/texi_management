<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Carbon;

class DateTimeStandardizationTest extends TestCase
{
    /**
     * A basic unit test to verify timezone and formatting.
     */
    public function test_timezone_is_ist(): void
    {
        $this->assertEquals('Asia/Kolkata', config('app.timezone'));
        
        // now() should be roughly equal to current time in IST
        $now = Carbon::now();
        $this->assertEquals('Asia/Kolkata', $now->timezoneName);
    }

    public function test_carbon_macros_work(): void
    {
        $date = Carbon::create(2026, 1, 26, 23, 5, 36);
        
        $this->assertEquals('26-01-2026', $date->toAppDate());
        $this->assertEquals('26-01-2026 23:05:36', $date->toAppDateTime());
    }

    public function test_config_changes_affect_formatting(): void
    {
        $date = Carbon::create(2026, 1, 26, 23, 5, 36);
        
        config(['app.date_format' => 'Y/m/d']);
        $this->assertEquals('2026/01/26', $date->toAppDate());
        
        config(['app.datetime_format' => 'Y/m/d H:i']);
        $this->assertEquals('2026/01/26 23:05', $date->toAppDateTime());
    }
}
