<?php

namespace App\Console\Commands;

use App\Jobs\CheckDelayedDutiesJob;
use Illuminate\Console\Command;

class CheckDelayedDutiesCommand extends Command
{
    protected $signature = 'duties:check-delayed';

    protected $description = 'Check for delayed duties and send alerts to drivers. Runs every minute in scheduler.';

    public function handle(): int
    {
        $this->info('🔍 Checking for delayed duties...');

        try {
            (new CheckDelayedDutiesJob())->handle(
                resolve('App\Services\WhatsAppService')
            );
            $this->info('✅ Delayed duties check completed successfully.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
