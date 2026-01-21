<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Messaging\AndroidConfig;
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
            
            // Add essential data for background notifications
            $notificationData = array_merge($data, [
                'link' => $link,
                'title' => $title,
                'body' => $body,
                'icon' => $icon,
                'click_action' => $link,
                'timestamp' => now()->timestamp,
            ]);

            // Build the message with proper configuration
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(
                    Notification::create($title, $body)
                        ->withImageUrl($icon)
                )
                ->withData($notificationData);

            // Web Push Configuration for browsers
            $webPushConfig = WebPushConfig::fromArray([
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'icon' => $icon,
                    'badge' => $icon,
                    'sound' => $soundPath,
                    'requireInteraction' => true, // Keeps notification until user interacts
                    'tag' => 'notification-' . time(), // Unique tag for each notification
                    'renotify' => true,
                    'vibrate' => [200, 100, 200],
                    'timestamp' => now()->timestamp * 1000,
                ],
                'fcm_options' => [
                    'link' => $link,
                ],
                'headers' => [
                    'TTL' => '86400', // Time to live: 24 hours
                    'Urgency' => 'high',
                ],
            ]);

            $message = $message->withWebPushConfig($webPushConfig);

            // Android Configuration
            $androidConfig = AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'sound' => $soundFile === 'default' ? 'default' : $soundFile,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'channel_id' => 'high_importance_channel',
                    'priority' => 'high',
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                    'default_light_settings' => true,
                ],
                'ttl' => '86400s', // 24 hours
            ]);

            $message = $message->withAndroidConfig($androidConfig);

            // Send the message
            $result = $messaging->send($message);

            Log::info('FCM Notification Sent Successfully', [
                'token' => substr($deviceToken, 0, 20) . '...',
                'title' => $title,
                'result' => $result,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('FCM Send Error: ' . $e->getMessage(), [
                'token' => substr($deviceToken, 0, 20) . '...',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'trace' => $e->getTraceAsString(),
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

            $logo = \App\Models\Setting::get('company_logo');
            $icon = $logo ? asset('storage/' . $logo) : asset('favicon.ico');

            $notificationData = array_merge($data, [
                'title' => $title,
                'body' => $body,
                'icon' => $icon,
                'timestamp' => now()->timestamp,
            ]);

            $notification = Notification::create($title, $body)
                ->withImageUrl($icon);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($notificationData);

            $report = $messaging->sendMulticast($message, $deviceTokens);

            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();

            Log::info('FCM Multicast Sent', [
                'success' => $successCount,
                'failure' => $failureCount,
            ]);

            return [
                'success' => $successCount,
                'failure' => $failureCount,
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

            $logo = \App\Models\Setting::get('company_logo');
            $icon = $logo ? asset('storage/' . $logo) : asset('favicon.ico');

            $notificationData = array_merge($data, [
                'title' => $title,
                'body' => $body,
                'icon' => $icon,
                'timestamp' => now()->timestamp,
            ]);

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(
                    Notification::create($title, $body)
                        ->withImageUrl($icon)
                )
                ->withData($notificationData);

            $messaging->send($message);

            Log::info('FCM Topic Notification Sent', [
                'topic' => $topic,
                'title' => $title,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('FCM Topic Send Error: ' . $e->getMessage(), [
                'topic' => $topic,
                'title' => $title,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Validate if a device token is still valid
     *
     * @param string $deviceToken
     * @return bool
     */
    public function validateToken(string $deviceToken): bool
    {
        try {
            $messaging = Firebase::messaging();
            
            // Send a dry-run message to validate token
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withData(['validation' => 'true']);
            
            $messaging->validate($message);
            
            return true;
        } catch (Exception $e) {
            Log::warning('Invalid FCM Token', [
                'token' => substr($deviceToken, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}