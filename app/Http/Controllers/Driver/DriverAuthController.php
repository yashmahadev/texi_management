<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\OtpVerification;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DriverAuthController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function showLogin()
    {
        if (Auth::guard('driver')->check()) {
            return redirect()->route('driver.dashboard');
        }
        return view('driver.auth.login');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|string|exists:drivers,mobile_number',
        ], [
            'mobile_number.required' => 'Please enter your mobile number.',
            'mobile_number.exists' => 'This mobile number is not registered in our system.',
        ]);

        $otp = env('APP_ENV') == 'local' ? '123456' :(string) rand(100000, 999999);
        $expiry = now()->addMinutes(10);

        OtpVerification::create([
            'phone_number' => $request->mobile_number,
            'otp' => $otp, // In production, hash this!
            'expires_at' => $expiry,
        ]);

        $this->whatsapp->sendOTP($request->mobile_number, $otp);

        session(['auth_mobile' => $request->mobile_number]);

        return redirect()->route('driver.login.verify');
    }

    public function showVerify()
    {
        if (Auth::guard('driver')->check()) {
            return redirect()->route('driver.dashboard');
        }
        if (!session('auth_mobile')) {
            return redirect()->route('driver.login');
        }
        return view('driver.auth.verify', ['mobile_number' => session('auth_mobile')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ], [
            'otp.required' => 'Please enter the 6-digit OTP.',
            'otp.size' => 'The OTP must be exactly 6 digits.',
        ]);

        $mobile = session('auth_mobile');
        if (!$mobile) {
            return redirect()->route('driver.login');
        }

        $record = OtpVerification::where('phone_number', $mobile)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->whereNull('verified_at')
            ->first();

        if (!$record) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }

        $record->update(['verified_at' => now()]);

        $driver = Driver::where('mobile_number', $mobile)->firstOrFail();
        Auth::guard('driver')->login($driver);

        $request->session()->forget('auth_mobile');
        $request->session()->regenerate();
        $request->session()->flash('fcm_sync_required', true);

        return redirect()->route('driver.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('driver')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('driver.login');
    }
}
