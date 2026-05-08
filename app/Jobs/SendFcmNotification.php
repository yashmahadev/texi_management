<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFcmNotification implements ShouldQueue
{
    use Queueable;

    public $token;
    public $title;
    public $body;
    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct(string $token, string $title, string $body, array $data = [])
    {
        $this->token = $token;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\NotificationService $notificationService): void
    {
        $notificationService->sendNotification(
            $this->token,
            $this->title,
            $this->body,
            $this->data
        );
    }
}
