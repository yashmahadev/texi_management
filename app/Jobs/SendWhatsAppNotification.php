<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;

    public $phoneNumber;
    public $type;
    public $data;
    public $isTemplate;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phoneNumber, string $type, array $data = [], bool $isTemplate = true)
    {
        $this->phoneNumber = $phoneNumber;
        $this->type = $type;
        $this->data = $data;
        $this->isTemplate = $isTemplate;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\WhatsAppService $whatsapp): void
    {
        if ($this->isTemplate) {
            $whatsapp->sendWithTemplate($this->phoneNumber, $this->type, $this->data);
        } else {
            // Logic for direct message if needed, though most are templates now
        }
    }
}
