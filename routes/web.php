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

use App\Http\Controllers\DevTestController;

Route::get('/', function () {
    return redirect()->route('driver.login');
});

// Dev & Test Routes (Consider protecting these in production)
Route::middleware(['auth:web,driver'])->group(function () {
    Route::get('/test-whatsapp', [DevTestController::class, 'testWhatsApp']);
    Route::get('/test-fcm', [DevTestController::class, 'testFcm']);
    Route::post('/update-fcm-token', [DevTestController::class, 'updateFcmToken'])->name('update-fcm-token');
    Route::get('/test-whatsapp-template', [DevTestController::class, 'testWhatsAppTemplate']);
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
    
    Route::middleware('throttle:500,1')->group(function () {
        Route::get('login', [DriverAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [DriverAuthController::class, 'sendOtp'])->name('login.send-otp');
        Route::get('verify', [DriverAuthController::class, 'showVerify'])->name('login.verify');
        Route::post('verify', [DriverAuthController::class, 'verifyOtp'])->name('login.verify.post');
    });
    
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