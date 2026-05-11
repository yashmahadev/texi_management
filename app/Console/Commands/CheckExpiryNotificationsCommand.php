<?php

namespace App\Console\Commands;

use App\Jobs\CheckExpiryNotificationsJob;
use Illuminate\Console\Command;

class CheckExpiryNotificationsCommand extends Command
{
    protected $signature = 'documents:check-expiry';

    protected $description = 'Check for expiring documents (DL, PUC, Insurance) and send notifications to drivers and admins. Runs daily at 08:00 in scheduler.';

    public function handle(): int
    {
        $this->info('⚠️  Checking for expiring documents (DL, PUC, Insurance)...');

        try {
            (new CheckExpiryNotificationsJob())->handle(
                resolve('App\Services\NotificationService'),
                resolve('App\Services\WhatsAppService')
            );
            $this->info('✅ Expiry notifications check completed successfully.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
