<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MonthlyDutyController;
use App\Http\Controllers\Admin\DailyDutyLogController;
use App\Http\Controllers\Admin\DutyReplacementController;
use App\Http\Controllers\Admin\ReportController;

use App\Http\Controllers\Driver\DriverAuthController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\DutyController;

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
