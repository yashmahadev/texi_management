<?php

namespace App\Services;

use App\Traits\FCMPushNotification;

class NotificationService
{
    use FCMPushNotification;

    public function sendNotification($fcmToken, $title, $body, $data = [], $recipientType = 'driver')
    {
        // Credentials are automatically loaded from file
        return $this->sendPushNotification(
            $fcmToken,
            $title,
            $body,
            $data,
            $recipientType
        );
    }
}