<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Driver\AuthController;
use App\Http\Controllers\Api\Driver\DashboardController;
use App\Http\Controllers\Api\Driver\DutyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Driver API Routes
Route::prefix('driver')->group(function () {
    // Auth
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify', [AuthController::class, 'verify']);
    
    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('update-fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::get('dashboard', [DashboardController::class, 'index']);
        
        // Duty Actions
        Route::get('duty/history', [DutyController::class, 'history']);
        Route::post('duty/{log}/start', [DutyController::class, 'start']);
        Route::post('duty/{log}/end', [DutyController::class, 'end']);
    });
});
