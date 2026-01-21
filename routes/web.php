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

Route::get('/test-whatsapp', function (\App\Services\WhatsAppService $whatsapp) {
    $to = '+918690065830';

    // 1. Test Template by Direct Name
    $res1 = $whatsapp->sendByName('test', $to, ["1" => "12/1", "2" => "3pm"]);
    
    // 2. Test Dynamic Duty Assignment (Will fall back to text if template SID missing)
    $res2 = $whatsapp->sendDutyAssignment($to, [
        'driver_name' => 'John Doe',
        'vehicle_number' => 'GJ01AB1234',
        'reporting_time' => '10:00 AM',
        'reporting_address' => 'Airport Terminal 1'
    ]);
    
    // 3. Test Direct Notification call
    $res3 = $whatsapp->sendNotification($to, 'test', ["1" => "OTP", "2" => "9999"]);

    return [
        'template_test_success' => $res1,
        'duty_assignment_success' => $res2,
        'notification_success' => $res3,
        'message' => 'Multiple templates attempted. Check logs/phone.'
    ];
});

Route::get('/test-fcm', function (Illuminate\Http\Request $request, \App\Services\FcmService $fcm) {
    $token = $request->query('token');
    $title = $request->query('title', 'Test Notification');
    $body = $request->query('body', 'This is a test notification from Laravel.');

    if (!$token) {
        $token = Driver::where('mobile_number', '8690065830')->first()->fcm_token ?? $token;
        // return response()->json([
        //     'error' => 'Device token is required. Use ?token=YOUR_TOKEN',
        //     'hint' => 'You also need to place your Firebase Service Account JSON at storage/app/firebase-auth.json'
        // ], 400);
    }
    // dd($token);

    $url = route('driver.dashboard');
    $success = $fcm->sendNotification($token, $title, $body, $url);

    return response()->json([
        'success' => $success,
        'message' => $success ? 'Notification sent successfully' : 'Failed to send notification. Check logs.',
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
        Route::get('reports', [ReportController::class, 'index'])
            ->name('reports.index')
            ->middleware('can:view_reports');
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

        // Modules
        Route::resource('vehicles', \App\Http\Controllers\Admin\VehicleController::class);

        
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
    });
});
