<?php

namespace Database\Seeders;

use App\Models\Owner;
use Illuminate\Database\Seeder;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        $owners = [
            [
                'name' => 'A1 Travels & Logistics',
                'mobile' => '8181818181',
                'address' => '123 Business Hub, Ahmedabad, Gujarat',
                'aadhaar_number' => '123456789012',
                'pancard_number' => 'ABCDE1234F',
            ],
            [
                'name' => 'Shiva Logistics Solutions',
                'mobile' => '8282828282',
                'address' => '456 Transport Plaza, Surat, Gujarat',
                'aadhaar_number' => '223456789012',
                'pancard_number' => 'BCDEF1234G',
            ],
            [
                'name' => 'Gujarat Cab Corp',
                'mobile' => '8383838383',
                'address' => '789 City Center, Vadodara, Gujarat',
                'aadhaar_number' => '323456789012',
                'pancard_number' => 'CDEFG1234H',
            ],
            [
                'name' => 'Rajesh Sharma (Self-Owner)',
                'mobile' => '9876543210',
                'address' => 'Plot 12, Sahyog Nagar, Ahmedabad',
                'aadhaar_number' => '423456789012',
                'pancard_number' => 'DEFGH1234I',
            ],
        ];

        foreach ($owners as $owner) {
            Owner::updateOrCreate(['mobile' => $owner['mobile']], $owner);
        }
    }
}
