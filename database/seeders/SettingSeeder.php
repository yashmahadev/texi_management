<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'app_name' => 'Taxi Management System',
            'support_email' => 'support@example.com',
            'support_mobile' => '+919876543210',
            'whatsapp_api_enabled' => '1',
            'push_notifications_enabled' => '1',
            'default_km_rate' => '15',
            'company_address' => 'A-101, Business Park, Ahmedabad, Gujarat',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
