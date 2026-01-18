<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            // Government
            ['name' => 'Gujarat Police - Traffic Branch', 'group' => 'Government'],
            ['name' => 'Revenue Department - Collector Office', 'group' => 'Government'],
            ['name' => 'Municipal Corporation - West Zone', 'group' => 'Government'],
            ['name' => 'State Transport - Regional Office', 'group' => 'Government'],
            
            // Corporate
            ['name' => 'Reliance Industries Ltd', 'group' => 'Corporate'],
            ['name' => 'Tata Consultancy Services', 'group' => 'Corporate'],
            ['name' => 'Adani Port Terminal', 'group' => 'Corporate'],
            ['name' => 'HDFC Bank - Regional Hub', 'group' => 'Corporate'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
