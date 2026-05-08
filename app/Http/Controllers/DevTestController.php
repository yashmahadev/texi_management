<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Services\NotificationService;
use App\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DevTestController extends Controller
{
    public function testWhatsApp(Request $request, WhatsAppService $whatsapp)
    {
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
    }

    public function testFcm(Request $request, NotificationService $notificationService)
    {
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
    }

    public function updateFcmToken(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
            ]);

            $adminCheck = auth('web')->check();
            $driverCheck = auth('driver')->check();

            Log::info('FCM Token Sync Start', [
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
                Log::info('FCM Token saved for Admin', ['id' => $user->id, 'email' => $user->email]);
                $updated = true;
            }
            
            // Update Driver
            if ($driverCheck) {
                $driver = auth('driver')->user();
                $driver->fcm_token = $request->token;
                $driver->save();
                Log::info('FCM Token saved for Driver', ['id' => $driver->id, 'mobile' => $driver->mobile_number]);
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

            Log::warning('FCM Token Sync failed: No active sessions found.');
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        } catch (\Exception $e) {
            Log::error('FCM Token Sync Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Internal server error during sync'], 500);
        }
    }

    public function testWhatsAppTemplate(Request $request, WhatsAppService $whatsapp)
    {
        $to = $request->query('to');
        $template = $request->query('template', 'otp');
        
        if (!$to) {
            return response()->json([
                'error' => 'Phone number is required. Use ?to=9876543210',
                'available_templates' => ['otp', 'duty_assigned', 'delay_alert', 'duty_cancelled', 'payment_invoice']
            ], 400);
        }

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
    }
}
