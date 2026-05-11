<?php

namespace App\Console\Commands;

use App\Jobs\MarkMissingDutiesJob;
use Illuminate\Console\Command;

class MarkMissingDutiesCommand extends Command
{
    protected $signature = 'duties:mark-missing';

    protected $description = 'Mark pending duties from yesterday or older as missing. Runs every minute in scheduler.';

    public function handle(): int
    {
        $this->info('📋 Marking missing duties...');

        try {
            (new MarkMissingDutiesJob())->handle();
            $this->info('✅ Missing duties marked successfully.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
