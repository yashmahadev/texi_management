<?php

namespace App\Traits;

use Exception;

trait FCMPushNotification
{
    /**
     * Send push notification via FCM
     *
     * @param string|array $fcmToken Single token or array of tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param string $recipientType Type of recipient (driver, admin, customer, etc.)
     * @param array $options Additional FCM options (priority, sound, badge, etc.)
     * @return array Response from FCM
     * @throws Exception
     */
    public function sendPushNotification($fcmToken, string $title, string $body, array $data = [], string $recipientType = 'driver', array $options = []): array
    {
        $credentials = $this->loadFirebaseCredentials();
        $accessToken = $this->getAccessToken();
        $projectId = $credentials['project_id'];
        
        $tokens = is_array($fcmToken) ? $fcmToken : [$fcmToken];
        $results = [];
        
        foreach ($tokens as $token) {
            $result = $this->sendToSingleDevice($accessToken, $projectId, $token, $title, $body, $data, $recipientType, $options);
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * Send notification to a single device
     */
    private function sendToSingleDevice(string $accessToken, string $projectId, string $token, string $title, string $body, array $data, string $recipientType, array $options): array
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        // Get logo and icon
        $logo = $this->getSetting('company_logo');
        $icon = $logo ? asset('storage/' . $logo) : asset('favicon.ico');
        
        // Get notification sound
        $soundFile = $this->getSetting('notification_sound', 'default');
        $soundPath = $soundFile !== 'default' ? asset('storage/' . $soundFile) : 'default';
        
        // Determine default link based on recipient type
        $defaultLink = $this->getDefaultLinkByRecipientType($recipientType);
        $link = $data['link'] ?? $defaultLink;
        
        // Add essential data for background notifications
        $notificationData = array_merge($data, [
            'link' => $link,
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'click_action' => $link,
            'timestamp' => time(),
        ]);
        
        // Convert all data values to strings (FCM requirement)
        $notificationData = array_map(function($value) {
            return is_array($value) ? json_encode($value) : (string)$value;
        }, $notificationData);
        
        $notification = [
            'title' => $title,
            'body' => $body,
            'image' => $icon,
        ];
        
        $message = [
            'message' => [
                'token' => $token,
                'notification' => $notification,
                'data' => $notificationData,
            ]
        ];
        
        // Add Android-specific config (V1 API compliant)
        $message['message']['android'] = [
            'priority' => 'high',
            'ttl' => '86400s',
            'notification' => [
                'sound' => $soundFile === 'default' ? 'default' : $soundFile,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'channel_id' => $options['channel_id'] ?? 'high_importance_channel',
                'notification_priority' => 'PRIORITY_HIGH',
                'default_sound' => true,
                'default_vibrate_timings' => true,
                'default_light_settings' => true,
            ]
        ];
        
        // Add Web Push config for browser notifications
        $message['message']['webpush'] = [
            'notification' => [
                'sound' => $soundPath,
                'requireInteraction' => true,
                'tag' => $options['tag'] ?? 'duty-alert',
                'renotify' => true,
                'icon' => $icon,
            ],
            'fcm_options' => [
                'link' => $link,
            ],
            'headers' => [
                'Urgency' => 'high',
            ],
        ];
        
        // Add iOS-specific config (APNS)
        $message['message']['apns'] = [
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'sound' => $soundFile === 'default' ? 'default' : $soundFile,
                    'badge' => $options['badge'] ?? 1,
                    'mutable-content' => 1,
                    'category' => 'NOTIFICATION_CATEGORY',
                ]
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->logNotificationError($token, $title, $body, $data, $error);
            throw new Exception("cURL Error: {$error}");
        }
        
        curl_close($ch);
        
        $success = $httpCode === 200;
        
        if ($success) {
            $this->logNotificationSuccess($token, $title, json_decode($response, true));
        } else {
            $this->logNotificationError($token, $title, $body, $data, $response);
        }
        
        return [
            'token' => $token,
            'status_code' => $httpCode,
            'response' => json_decode($response, true),
            'success' => $success
        ];
    }
    
    /**
     * Load Firebase credentials from storage file
     * 
     * @return array Firebase service account credentials
     * @throws Exception
     */
    private function loadFirebaseCredentials(): array
    {
        // Try multiple possible paths
        $possiblePaths = [
            storage_path('app/firebase-auth.json'),
            storage_path('app/firebaseauth.json'),
            base_path('storage/app/firebase-auth.json'),
        ];
        
        $credentialsPath = null;
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $credentialsPath = $path;
                break;
            }
        }
        
        if (!$credentialsPath) {
            throw new Exception('Firebase credentials file not found. Please ensure firebase-auth.json exists in storage/app/ directory.');
        }
        
        $credentialsJson = file_get_contents($credentialsPath);
        
        if ($credentialsJson === false) {
            throw new Exception('Failed to read Firebase credentials file.');
        }
        
        $credentials = json_decode($credentialsJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in Firebase credentials file: ' . json_last_error_msg());
        }
        
        // Validate required fields
        $requiredFields = ['type', 'project_id', 'private_key', 'client_email'];
        foreach ($requiredFields as $field) {
            if (!isset($credentials[$field])) {
                throw new Exception("Missing required field '{$field}' in Firebase credentials file.");
            }
        }
        
        return $credentials;
    }
    
    /**
     * Get OAuth2 access token using service account
     */
    private function getAccessToken(): string
    {
        $serviceAccount = $this->loadFirebaseCredentials();
        
        $now = time();
        $expiration = $now + 3600;
        
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        $claimSet = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $expiration,
            'iat' => $now
        ];
        
        $jwt = $this->createJWT($header, $claimSet, $serviceAccount['private_key']);
        
        // Exchange JWT for access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (!isset($result['access_token'])) {
            throw new Exception('Failed to obtain access token: ' . $response);
        }
        
        return $result['access_token'];
    }
    
    /**
     * Create JWT token
     */
    private function createJWT(array $header, array $payload, string $privateKey): string
    {
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        
        $signature = '';
        openssl_sign(
            $base64UrlHeader . '.' . $base64UrlPayload,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );
        
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Send notification to multiple devices (batch)
     *
     * @param array $tokens Array of FCM tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param string $recipientType Type of recipient
     * @param array $options Additional FCM options
     * @return array Results for each token
     */
    public function sendBatchNotification(array $tokens, string $title, string $body, array $data = [], string $recipientType = 'driver', array $options = []): array
    {
        return $this->sendPushNotification($tokens, $title, $body, $data, $recipientType, $options);
    }
    
    /**
     * Send notification to a topic
     *
     * @param string $topic Topic name
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param string $recipientType Type of recipient
     * @param array $options Additional FCM options
     * @return array Response from FCM
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [], string $recipientType = 'driver', array $options = []): array
    {
        $credentials = $this->loadFirebaseCredentials();
        $accessToken = $this->getAccessToken();
        $projectId = $credentials['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        // Get logo and icon
        $logo = $this->getSetting('company_logo');
        $icon = $logo ? asset('storage/' . $logo) : asset('favicon.ico');
        
        // Determine default link based on recipient type
        $defaultLink = $this->getDefaultLinkByRecipientType($recipientType);
        $link = $data['link'] ?? $defaultLink;
        
        // Add essential data for background notifications
        $notificationData = array_merge($data, [
            'link' => $link,
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'click_action' => $link,
            'timestamp' => time(),
        ]);
        
        // Convert all data values to strings
        $notificationData = array_map(function($value) {
            return is_array($value) ? json_encode($value) : (string)$value;
        }, $notificationData);
        
        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'image' => $icon,
                ],
                'data' => $notificationData,
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'topic' => $topic,
            'status_code' => $httpCode,
            'response' => json_decode($response, true),
            'success' => $httpCode === 200
        ];
    }
    
    /**
     * Get setting value (override this method in your class if needed)
     */
    protected function getSetting(string $key, $default = null)
    {
        // Check if Setting model exists and use it
        if (class_exists('\App\Models\Setting')) {
            return \App\Models\Setting::get($key, $default);
        }
        
        return $default;
    }
    
    /**
     * Get default link based on recipient type
     */
    protected function getDefaultLinkByRecipientType(string $recipientType): string
    {
        $links = [
            'admin' => function_exists('route') ? route('admin.dashboard') : '/admin/dashboard',
            'driver' => function_exists('route') ? route('driver.dashboard') : '/driver/dashboard',
        ];
        
        return $links[$recipientType] ?? ($links['driver'] ?? '/dashboard');
    }
    
    /**
     * Log notification success
     */
    protected function logNotificationSuccess(string $token, string $title, $result): void
    {
        if (class_exists('\Illuminate\Support\Facades\Log')) {
            \Illuminate\Support\Facades\Log::info('FCM Notification Sent Successfully', [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title,
                'result' => $result,
            ]);
        }
    }
    
    /**
     * Log notification error
     */
    protected function logNotificationError(string $token, string $title, string $body, array $data, string $error): void
    {
        if (class_exists('\Illuminate\Support\Facades\Log')) {
            \Illuminate\Support\Facades\Log::error('FCM Send Error: ' . $error, [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }
    }
}