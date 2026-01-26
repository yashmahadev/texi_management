<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\DirectBooking;
use App\Models\BookingAssignment;
use App\Models\BookingStatusLog;
use App\Models\BookingFare;
use App\Models\BookingCancellation;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\User;

class DirectBookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user (admin) for created_by
        $admin = User::first();
        if (!$admin) {
            $this->command->error('No admin user found. Please create an admin first.');
            return;
        }

        // Create sample customers
        $customers = [
            ['name' => 'Rajesh Kumar', 'mobile' => '9876543210', 'email' => 'rajesh@example.com', 'address' => 'MG Road, Ahmedabad', 'status' => 'active'],
            ['name' => 'Priya Patel', 'mobile' => '9876543211', 'email' => 'priya@example.com', 'address' => 'SG Highway, Ahmedabad', 'status' => 'active'],
            ['name' => 'Amit Shah', 'mobile' => '9876543212', 'email' => 'amit@example.com', 'address' => 'Satellite, Ahmedabad', 'status' => 'active'],
            ['name' => 'Neha Desai', 'mobile' => '9876543213', 'email' => 'neha@example.com', 'address' => 'Vastrapur, Ahmedabad', 'status' => 'active'],
            ['name' => 'Karan Mehta', 'mobile' => '9876543214', 'email' => 'karan@example.com', 'address' => 'Bodakdev, Ahmedabad', 'status' => 'active'],
        ];

        foreach ($customers as $customerData) {
            Customer::firstOrCreate(
                ['mobile' => $customerData['mobile']],
                $customerData
            );
        }

        $this->command->info('Sample customers created successfully!');

        // Get drivers and vehicles
        $drivers = Driver::where('status', 'active')->limit(3)->get();
        $vehicles = Vehicle::where('status', 'active')->limit(3)->get();

        if ($drivers->isEmpty() || $vehicles->isEmpty()) {
            $this->command->warn('No active drivers or vehicles found. Skipping booking creation.');
            return;
        }

        // Create sample bookings with various statuses
        $bookings = [
            [
                'customer_name' => 'Rajesh Kumar',
                'customer_mobile' => '9876543210',
                'pickup_location' => 'Ahmedabad Railway Station',
                'drop_location' => 'Ahmedabad Airport',
                'booking_datetime' => now()->addDays(2)->setTime(10, 0),
                'estimated_km' => 15,
                'status' => 'CREATED',
                'notes' => 'Please call before arrival',
            ],
            [
                'customer_name' => 'Priya Patel',
                'customer_mobile' => '9876543211',
                'pickup_location' => 'SG Highway,  Corporate Park',
                'drop_location' => 'Gandhin agar Secretariat',
                'booking_datetime' => now()->addDays(1)->setTime(9, 30),
                'estimated_km' => 25,
                'status' => 'ASSIGNED',
                'notes' => 'Office pickup',
            ],
            [
                'customer_name' => 'Amit Shah',
                'customer_mobile' => '9876543212',
                'pickup_location' => 'Satellite, ISRO Colony',
                'drop_location' => 'Law Garden',
                'booking_datetime' => now()->addHours(3),
                'estimated_km' => 8,
                'status' => 'ACCEPTED',
                'notes' => 'Urgent trip',
            ],
            [
                'customer_name' => 'Walk-in Customer',
                'customer_mobile' => '9999999999',
                'pickup_location' => 'Vastrapur Lake',
                'drop_location' => 'Kankaria Lake',
                'booking_datetime' => now()->subDays(1)->setTime(15, 0),
                'estimated_km' => 12,
                'actual_km' => 14,
                'status' => 'COMPLETED',
                'notes' => 'Direct walk-in booking',
            ],
            [
                'customer_name' => 'Karan Mehta',
                'customer_mobile' => '9876543214',
                'pickup_location' => 'Bodakdev Circle',
                'drop_location' => 'Maninagar Railway Station',
                'booking_datetime' => now()->addDays(5)->setTime(18, 0),
                'estimated_km' => 18,
                'status' => 'CANCELLED',
                'notes' => 'Evening trip',
            ],
        ];

        foreach ($bookings as $index => $bookingData) {
            $booking = DirectBooking::create(array_merge($bookingData, [
                'created_by' => $admin->id,
            ]));

            // Log initial status
            BookingStatusLog::create([
                'booking_id' => $booking->id,
                'from_status' => null,
                'to_status' => $bookingData['status'],
                'changed_by' => $admin->id,
                'changed_by_type' => User::class,
                'remarks' => 'Booking created',
            ]);

            // Assign driver for bookings that need it
            if (in_array($booking->status, ['ASSIGNED', 'ACCEPTED', 'COMPLETED'])) {
                $driver = $drivers[$index % $drivers->count()];
                $vehicle = $vehicles[$index % $vehicles->count()];

                $assignment = BookingAssignment::create([
                    'booking_id' => $booking->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'assigned_at' => now(),
                    'assigned_by' => $admin->id,
                    'is_active' => true,
                ]);

                BookingStatusLog::create([
                    'booking_id' => $booking->id,
                    'from_status' => 'CREATED',
                    'to_status' => 'ASSIGNED',
                    'changed_by' => $admin->id,
                    'changed_by_type' => User::class,
                    'remarks' => "Driver {$driver->name} assigned",
                    'metadata' => ['driver_id' => $driver->id, 'vehicle_id' => $vehicle->id],
                ]);
            }

            // Create fare for completed booking
            if ($booking->status === 'COMPLETED') {
                $baseFare = 50;
                $perKmRate = 10;
                $calculatedFare = $baseFare + ($booking->actual_km * $perKmRate);

                BookingFare::create([
                    'booking_id' => $booking->id,
                    'base_fare' => $baseFare,
                    'per_km_rate' => $perKmRate,
                    'total_km' => $booking->actual_km,
                    'calculated_fare' => $calculatedFare,
                    'final_fare' => $calculatedFare,
                    'is_locked' => true,
                    'calculated_by' => $admin->id,
                ]);

                // Add completion status log
                BookingStatusLog::create([
                    'booking_id' => $booking->id,
                    'from_status' => 'STARTED',
                    'to_status' => 'COMPLETED',
                    'changed_by' => $drivers->first()->id,
                    'changed_by_type' => Driver::class,
                    'remarks' => 'Trip completed successfully',
                    'metadata' => ['actual_km' => $booking->actual_km],
                ]);
            }

            // Create cancellation record for cancelled booking
            if ($booking->status === 'CANCELLED') {
                BookingCancellation::create([
                    'booking_id' => $booking->id,
                    'cancelled_by' => $admin->id,
                    'cancelled_by_type' => User::class,
                    'cancellation_reason' => 'Customer requested cancellation due to change in plans',
                    'cancelled_at' => now(),
                ]);

                BookingStatusLog::create([
                    'booking_id' => $booking->id,
                    'from_status' => 'CREATED',
                    'to_status' => 'CANCELLED',
                    'changed_by' => $admin->id,
                    'changed_by_type' => User::class,
                    'remarks' => 'Booking cancelled by admin',
                ]);
            }
        }

        $this->command->info('Sample direct bookings created successfully!');
        $this->command->info('Created ' . count($bookings) . ' bookings with various statuses.');
    }
}
