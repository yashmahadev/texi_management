<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\OtpVerification;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function login(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|string|exists:drivers,mobile_number',
        ], [
            'mobile_number.exists' => 'This mobile number is not registered.',
        ]);

        $otp = '123456'; // Fixed for prod/dev parity as per original code, TODO: Randomize
        $expiry = now()->addMinutes(10);

        OtpVerification::create([
            'phone_number' => $request->mobile_number,
            'otp' => $otp,
            'expires_at' => $expiry,
        ]);

        // Send OTP via WhatsApp
        $this->whatsapp->sendOTP($request->mobile_number, $otp);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to your WhatsApp number.',
            'mobile_number' => $request->mobile_number
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $record = OtpVerification::where('phone_number', $request->mobile_number)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->whereNull('verified_at')
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.'
            ], 422);
        }

        $record->update(['verified_at' => now()]);

        $driver = Driver::where('mobile_number', $request->mobile_number)->firstOrFail();
        
        // Revoke all existing tokens if single session desired, or keep them. keeping them for now.
        $token = $driver->createToken('driver-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'driver' => $driver
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully'
        ]);
    }
}
