<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MonthlyDutyController;
use App\Http\Controllers\Admin\DailyDutyLogController;
use App\Http\Controllers\Admin\DutyReplacementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\DepartmentController;

use App\Http\Controllers\Driver\DriverAuthController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\DutyController;
use App\Models\Driver;

Route::get('/', function () {
    return redirect()->route('driver.login');
});

Route::get('/test-whatsapp', function (Illuminate\Http\Request $request, \App\Services\WhatsAppService $whatsapp) {
    $to = $request->query('to', '+918690065830');
    $type = $request->query('type', 'all');
    $results = [];

    if ($type === 'otp' || $type === 'all') {
        $results['otp'] = $whatsapp->sendOTP($to, '123456');
    }
    if ($type === 'delay' || $type === 'all') {
        $results['delay_alert'] = $whatsapp->sendDelayAlert($to, [
            'department' => 'Police Dept',
            'vehicle' => 'GJ01AB1234',
            'time' => '10:00 AM'
        ]);
    }
    if ($type === 'duty_assigned' || $type === 'all') {
        $results['duty_assigned'] = $whatsapp->sendDutyAssignment($to, [
            'driver_name' => 'Yash',
            'vehicle_number' => 'GJ01XY7890',
            'reporting_time' => '09:00 AM',
            'reporting_address' => 'District Court'
        ]);
    }
    if ($type === 'duty_cancelled' || $type === 'all') {
        $results['duty_cancelled'] = $whatsapp->sendDutyCancellation($to, '101');
    }
    if ($type === 'payment' || $type === 'all') {
        $results['payment_invoice'] = $whatsapp->sendInvoice($to, [
            'customer_name' => 'John Doe',
            'amount' => '1500',
            'bill_no' => 'INV-2024-001'
        ]);
    }

    return [
        'target' => $to,
        'selected_type' => $type,
        'results' => $results,
        'available_types' => ['all', 'otp', 'delay', 'duty_assigned', 'duty_cancelled', 'payment'],
        'message' => 'Test messages sent. Check your WhatsApp.'
    ];
});

Route::get('/test-fcm', function (Illuminate\Http\Request $request, \App\Services\NotificationService $notificationService) {
    if (!$request->has('token')) {
        $driver = Driver::where('mobile_number', '8690065830')->first();
        $token = $driver->fcm_token ?? null;
    } else {
        $token = $request->query('token');
    }

    if (!$token) {
        return response()->json(['error' => 'Device token not found for test driver and not provided in ?token='], 400);
    }

    $type = $request->query('type', 'all');
    $results = [];

    if ($type === 'assignment' || $type === 'all') {
        $results['assignment'] = $notificationService->sendNotification(
            $token,
            "🚕 New Monthly Duty!",
            "You have been assigned to Police Dept starting 25 Jan. Log in to view details.",
            ['link' => route('driver.dashboard')]
        );
    }

    if ($type === 'replacement' || $type === 'all') {
        $results['replacement'] = $notificationService->sendNotification(
            $token,
            "🔄 Replacement Duty Assigned",
            "You are assigned as a replacement for today's duty (24 Jan). Please report on time.",
            ['link' => route('driver.dashboard')]
        );
    }

    if ($type === 'cancellation' || $type === 'all') {
        $results['cancellation'] = $notificationService->sendNotification(
            $token,
            "🛑 Duty Cancelled",
            "Your duty for Public Works has been cancelled. Contact admin for details.",
            ['link' => route('driver.dashboard')]
        );
    }

    if ($type === 'delay' || $type === 'all') {
        $results['delay'] = $notificationService->sendNotification(
            $token,
            "⚠️ Alert: Duty Delay",
            "Your duty for Health Dept was due to start at 09:00 AM. Please report status.",
            ['link' => route('driver.dashboard')]
        );
    }

    if ($type === 'completion' || $type === 'all') {
        $results['completion'] = $notificationService->sendNotification(
            $token,
            "✅ Trip Completed!",
            "Total Distance: 45 KM. Summary recorded successfully.",
            ['link' => route('driver.history')]
        );
    }

    return response()->json([
        'target_token' => substr($token, 0, 15) . '...',
        'selected_type' => $type,
        'results' => $results,
        'available_types' => ['all', 'assignment', 'replacement', 'cancellation', 'delay', 'completion'],
        'message' => 'FCM test notifications attempted. Check your device/console.'
    ]);
});

Route::post('/update-fcm-token', function (Illuminate\Http\Request $request) {
    try {
        $request->validate([
            'token' => 'required|string',
        ]);

        $adminCheck = auth('web')->check();
        $driverCheck = auth('driver')->check();

        \Illuminate\Support\Facades\Log::info('FCM Token Sync Start', [
            'token_length' => strlen($request->token),
            'admin_auth' => $adminCheck,
            'driver_auth' => $driverCheck,
        ]);

        $updated = false;
        
        // Update Admin
        if ($adminCheck) {
            $user = auth('web')->user();
            $user->fcm_token = $request->token;
            $user->save();
            \Illuminate\Support\Facades\Log::info('FCM Token saved for Admin', ['id' => $user->id, 'email' => $user->email]);
            $updated = true;
        }
        
        // Update Driver
        if ($driverCheck) {
            $driver = auth('driver')->user();
            $driver->fcm_token = $request->token;
            $driver->save();
            \Illuminate\Support\Facades\Log::info('FCM Token saved for Driver', ['id' => $driver->id, 'mobile' => $driver->mobile_number]);
            $updated = true;
        }

        if ($updated) {
            return response()->json([
                'success' => true, 
                'message' => 'Token updated successfully',
                'sync_info' => [
                    'admin' => $adminCheck,
                    'driver' => $driverCheck
                ]
            ]);
        }

        \Illuminate\Support\Facades\Log::warning('FCM Token Sync failed: No active sessions found.');
        return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('FCM Token Sync Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['success' => false, 'message' => 'Internal server error during sync'], 500);
    }
})->name('update-fcm-token');

Route::get('/test-whatsapp-template', function (Illuminate\Http\Request $request, \App\Services\WhatsAppService $whatsapp) {
    $to = $request->query('to');
    $template = $request->query('template', 'otp');
    
    if (!$to) {
        return response()->json([
            'error' => 'Phone number is required. Use ?to=9876543210',
            'available_templates' => ['otp', 'duty_assigned', 'delay_alert', 'duty_cancelled', 'payment_invoice']
        ], 400);
    }

    // Example variables for various templates based on current config/services.php
    $vars = match($template) {
        'otp' => ["1" => "123456"],
        'duty_assigned' => [
            "1" => "John Doe",
            "2" => "GJ01AB1234",
            "3" => "10:00 AM",
            "4" => "Airport Terminal 1"
        ],
        'delay_alert' => [
            "1" => "Logistics",
            "2" => "GJ01AB1234",
            "3" => "09:30 AM"
        ],
        'duty_cancelled' => ["1" => "DUTY-123"],
        'payment_invoice' => [
            "1" => "Jane Smith",
            "2" => "1500",
            "3" => "INV-2024-001"
        ],
        default => ["1" => "Test Value"]
    };

    $success = $whatsapp->sendWithTemplate('+91'.$to, $template, $vars);

    return response()->json([
        'success' => $success,
        'message' => $success ? "Template '{$template}' sent to {$to}" : "Failed to send template '{$template}'",
        'hint' => 'Check Twilio logs or WhatsAppLog table/Laravel logs for details.'
    ]);
});

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    
    // Guest
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login']);
    });

    // Auth
    Route::middleware(['auth'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Profile
        Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('profile.index');
        Route::put('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
        
        Route::get('dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('can:view_admin_dashboard');
        
        Route::get('monthly-duties/vehicles-by-type', [MonthlyDutyController::class, 'getVehiclesByType'])
            ->name('monthly-duties.vehicles-by-type');

        Route::resource('monthly-duties', MonthlyDutyController::class);

        // Departments
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');

        // Vehicles
        Route::resource('vehicles', VehicleController::class);
        
        Route::get('daily-logs', [DailyDutyLogController::class, 'index'])
            ->name('daily-logs.index')
            ->middleware('can:view_daily_logs');
        Route::get('daily-logs/{log}', [DailyDutyLogController::class, 'show'])
            ->name('daily-logs.show')
            ->middleware('can:view_daily_logs');
        Route::patch('daily-logs/{log}/status', [DailyDutyLogController::class, 'updateStatus'])
            ->name('daily-logs.update-status')
            ->middleware('can:verify_daily_logs');
        Route::get('daily-logs/{log}/edit', [DailyDutyLogController::class, 'edit'])
            ->name('daily-logs.edit')
            ->middleware('can:edit_daily_logs');
        Route::put('daily-logs/{log}', [DailyDutyLogController::class, 'update'])
            ->name('daily-logs.update')
            ->middleware('can:edit_daily_logs');
        
        // Replacement
        Route::get('daily-logs/{log}/replace', [DutyReplacementController::class, 'create'])
            ->name('replacements.create')
            ->middleware('can:assign_replacements');
        Route::post('daily-logs/{log}/replace', [DutyReplacementController::class, 'store'])
            ->name('replacements.store')
            ->middleware('can:assign_replacements');
        Route::get('replacements', [DutyReplacementController::class, 'index'])
            ->name('replacements.index')
            ->middleware('can:view_replacements');
        
        // Reports
        Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])
            ->name('reports.index')
            ->middleware('can:view_reports');
        
        // Phase-2: Direct Booking Reports
        Route::prefix('reports/direct-bookings')->name('reports.direct-bookings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DirectBookingReportController::class, 'index'])
                ->name('index')->middleware('can:view_reports');
            Route::get('/daily-trips', [\App\Http\Controllers\Admin\DirectBookingReportController::class, 'dailyTrips'])
                ->name('daily-trips')->middleware('can:view_reports');
            Route::get('/revenue', [\App\Http\Controllers\Admin\DirectBookingReportController::class, 'revenueSummary'])
                ->name('revenue')->middleware('can:view_reports');
        });
        Route::get('reports/bill-processing', [ReportController::class, 'billProcessing'])
            ->name('reports.bill-processing')
            ->middleware('can:view_reports');
        Route::post('reports/bill-processing/download', [ReportController::class, 'downloadBillProcessing'])
            ->name('reports.bill-processing.download')
            ->middleware('can:view_reports');
        Route::post('reports/bill-processing/preview-download', [ReportController::class, 'previewBillProcessingPDF'])
            ->name('reports.bill-processing.preview')
            ->middleware('can:view_reports');
        Route::get('reports/{monthlyDuty}/download', [ReportController::class, 'download'])
            ->name('reports.download')
            ->middleware('can:download_reports');

        // Phase-2: Customer Module
        Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
            ->middleware('can:view_customers');

        // Direct Booking
        Route::prefix('direct-bookings')->name('direct-bookings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DirectBookingController::class, 'index'])
                ->name('index')
                ->middleware('can:view_direct_bookings');
            Route::get('/create', [\App\Http\Controllers\Admin\DirectBookingController::class, 'create'])
                ->name('create')
                ->middleware('can:create_direct_bookings');
            Route::post('/', [\App\Http\Controllers\Admin\DirectBookingController::class, 'store'])
                ->name('store')
                ->middleware('can:create_direct_bookings');
        });
        
        // Access Control
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class)
            ->middleware('can:manage_roles');
        Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class)
            ->middleware('can:manage_permissions');

        // Audit Logs
        Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])
            ->name('audit-logs.index')
            ->middleware('can:view_audit_logs');

        // Settings
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])
            ->name('settings.index')
            ->middleware('can:manage_settings');
        Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])
            ->name('settings.update')
            ->middleware('can:manage_settings');

        // Phase-2: Customer Module
        Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
            ->middleware('can:view_customers');

        // Phase-2: Direct Bookings
        Route::prefix('direct-bookings')->name('direct-bookings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DirectBookingController::class, 'index'])
                ->name('index')->middleware('can:view_direct_bookings');
            Route::get('/create', [\App\Http\Controllers\Admin\DirectBookingController::class, 'create'])
                ->name('create')->middleware('can:create_direct_bookings');
            
            // IMPORTANT: This route must come BEFORE /{booking} to avoid route conflicts
            Route::get('/available-resources', [\App\Http\Controllers\Admin\DirectBookingController::class, 'getAvailableResources'])
                ->name('available-resources');
            
            Route::post('/', [\App\Http\Controllers\Admin\DirectBookingController::class, 'store'])
                ->name('store')->middleware('can:create_direct_bookings');
            Route::get('/{booking}', [\App\Http\Controllers\Admin\DirectBookingController::class, 'show'])
                ->name('show')->middleware('can:view_direct_bookings');
            Route::get('/{booking}/edit', [\App\Http\Controllers\Admin\DirectBookingController::class, 'edit'])
                ->name('edit')->middleware('can:edit_direct_bookings');
            Route::put('/{booking}', [\App\Http\Controllers\Admin\DirectBookingController::class, 'update'])
                ->name('update')->middleware('can:edit_direct_bookings');
            Route::post('/{booking}/assign', [\App\Http\Controllers\Admin\DirectBookingController::class, 'assignDriver'])
                ->name('assign')->middleware('can:assign_drivers_to_bookings');
            Route::post('/{booking}/cancel', [\App\Http\Controllers\Admin\DirectBookingController::class, 'cancel'])
                ->name('cancel')->middleware('can:cancel_bookings');
            Route::post('/{booking}/adjust-fare', [\App\Http\Controllers\Admin\DirectBookingController::class, 'adjustFare'])
                ->name('adjust-fare')->middleware('can:adjust_booking_fares');
        });
    });
});

// Driver Routes
Route::prefix('driver')->name('driver.')->group(function () {
    
    Route::get('login', [DriverAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [DriverAuthController::class, 'sendOtp'])->name('login.send-otp');
    Route::get('verify', [DriverAuthController::class, 'showVerify'])->name('login.verify');
    Route::post('verify', [DriverAuthController::class, 'verifyOtp'])->name('login.verify.post');
    
    Route::middleware('auth:driver')->group(function () {
        Route::post('logout', [DriverAuthController::class, 'logout'])->name('logout');
        
        Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');
        
        Route::post('duty/{log}/start', [DutyController::class, 'start'])->name('duty.start');
        Route::post('duty/{log}/end', [DutyController::class, 'end'])->name('duty.end');
        Route::get('history', [DutyController::class, 'history'])->name('history');

        // Phase-2: Direct Bookings for Drivers
        Route::prefix('direct-bookings')->name('direct-bookings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Driver\DirectBookingDriverController::class, 'index'])->name('index');
            Route::get('/{booking}', [\App\Http\Controllers\Driver\DirectBookingDriverController::class, 'show'])->name('show');
            Route::post('/{booking}/accept', [\App\Http\Controllers\Driver\DirectBookingDriverController::class, 'accept'])->name('accept');
            Route::post('/{booking}/reject', [\App\Http\Controllers\Driver\DirectBookingDriverController::class, 'reject'])->name('reject');
            Route::post('/{booking}/start', [\App\Http\Controllers\Driver\DirectBookingDriverController::class, 'start'])->name('start');
            Route::post('/{booking}/end', [\App\Http\Controllers\Driver\DirectBookingDriverController::class, 'end'])->name('end');
        });
    });
});


// Route::get('test-noti', function() {
//     (new \App\Services\FcmService())->sendWelcomeNotification();
// });