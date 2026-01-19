<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Exception;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send a notification to a specific device.
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $recipientType 'admin' or 'driver'
     * @return bool
     */
    public function sendNotification(string $deviceToken, string $title, string $body, array $data = [], string $recipientType = 'driver'): bool
    {
        try {
            $messaging = Firebase::messaging();

            $logo = \App\Models\Setting::get('company_logo');
            $icon = $logo ? asset('storage/' . $logo) : asset('favicon.ico');
            
            $soundFile = \App\Models\Setting::get('notification_sound', 'default');
            $soundPath = $soundFile !== 'default' ? asset('storage/' . $soundFile) : 'default';

            // Determine default link based on recipient type
            $defaultLink = $recipientType === 'admin' ? route('admin.dashboard') : route('driver.dashboard');
            $link = $data['link'] ?? $defaultLink;
            $data['link'] = $link;

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data)
                ->withWebPushConfig([
                    'notification' => [
                        'icon' => $icon,
                        'sound' => $soundPath,
                    ],
                    'fcm_options' => [
                        'link' => $link,
                    ],
                ])
                ->withAndroidConfig([
                    'notification' => [
                        'sound' => $soundFile === 'default' ? 'default' : $soundFile,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ]);

            $messaging->send($message);

            return true;
        } catch (Exception $e) {
            Log::error('FCM Send Error: ' . $e->getMessage(), [
                'token' => $deviceToken,
                'title' => $title,
                'body' => $body,
                'data' => $data
            ]);

            return false;
        }
    }

    /**
     * Send a notification to multiple devices.
     *
     * @param array $deviceTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendMulticastNotification(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        try {
            $messaging = Firebase::messaging();

            $notification = Notification::create($title, $body);
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            $report = $messaging->sendMulticast($message, $deviceTokens);

            return [
                'success' => $report->successes()->count(),
                'failure' => $report->failures()->count(),
                'errors' => collect($report->failures())->map(fn($f) => $f->error()->getMessage())->toArray(),
            ];
        } catch (Exception $e) {
            Log::error('FCM Multicast Send Error: ' . $e->getMessage());

            return [
                'success' => 0,
                'failure' => count($deviceTokens),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Send a notification to a specific topic.
     *
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $messaging = Firebase::messaging();

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $messaging->send($message);

            return true;
        } catch (Exception $e) {
            Log::error('FCM Topic Send Error: ' . $e->getMessage(), [
                'topic' => $topic,
                'title' => $title,
            ]);

            return false;
        }
    }
}
