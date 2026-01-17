<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected function getAccessToken()
    {
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            json_decode(file_get_contents(storage_path('app/firebase/firebase.json')), true)
        );

        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }

    public function sendNotification($fcmToken, $title, $body, $data = [])
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)->post(
            "https://fcm.googleapis.com/v1/projects/" . env('FIREBASE_PROJECT_ID') . "/messages:send",
            [
                "message" => [
                    "token" => $fcmToken,
                    "notification" => [
                        "title" => $title,
                        "body" => $body,
                    ],
                    "data" => $data,
                ]
            ]
        );

        return $response->json();
    }
}
