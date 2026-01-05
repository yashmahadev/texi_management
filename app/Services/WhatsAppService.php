<?php

namespace App\Services;

use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class WhatsAppService
{
    protected $client;
    protected $from;
    protected $templates;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->from = config('services.twilio.from');
        $this->templates = config('services.twilio.templates', []);

        if ($sid && $token) {
            $this->client = new Client($sid, $token);
        }

        // Ensure from number has whatsapp: prefix and no extra quotes/spaces
        if ($this->from) {
            $this->from = trim($this->from, " \"'");
            if (!str_starts_with($this->from, 'whatsapp:')) {
                $this->from = 'whatsapp:' . $this->from;
            }
        }
    }

    public function sendOTP(string $phoneNumber, string $otp)
    {
        if (isset($this->templates['otp'])) {
            return $this->sendByName('otp', $phoneNumber, ["1" => $otp]);
        }

        $message = "Your OTP for Duty Management System is: {$otp}. Valid for 10 minutes.";
        return $this->sendMessage($phoneNumber, 'otp', $message);
    }

    public function sendDelayAlert(string $phoneNumber, array $dutyDetails)
    {
        if (isset($this->templates['delay_alert'])) {
            return $this->sendByName('delay_alert', $phoneNumber, [
                "1" => $dutyDetails['department'],
                "2" => $dutyDetails['vehicle'],
                "3" => $dutyDetails['time']
            ]);
        }

        $department = $dutyDetails['department'];
        $vehicle = $dutyDetails['vehicle'];
        $time = $dutyDetails['time'];
        
        $message = "ALERT: Duty delay detected.\nDepartment: {$department}\nVehicle: {$vehicle}\nExpected Start: {$time}\nPlease start duty immediately or report issue.";
        
        return $this->sendMessage($phoneNumber, 'delay_alert', $message);
    }

    public function sendDutyAssignment(string $phoneNumber, array $details)
    {
        if (isset($this->templates['duty_assigned'])) {
            return $this->sendByName('duty_assigned', $phoneNumber, [
                "1" => $details['driver_name'],
                "2" => $details['vehicle_number'],
                "3" => $details['reporting_time'],
                "4" => $details['reporting_address'] ?? 'Not specified'
            ]);
        }

        $message = "New Duty Assigned!\nDriver: {$details['driver_name']}\nVehicle: {$details['vehicle_number']}\nTime: {$details['reporting_time']}\nLocation: {$details['reporting_address']}";
        return $this->sendMessage($phoneNumber, 'duty_assigned', $message);
    }

    public function sendDutyCancellation(string $phoneNumber, string $dutyId)
    {
        if (isset($this->templates['duty_cancelled'])) {
            return $this->sendByName('duty_cancelled', $phoneNumber, ["1" => $dutyId]);
        }

        $message = "IMPORTANT: Duty #{$dutyId} has been CANCELLED.";
        return $this->sendMessage($phoneNumber, 'duty_cancelled', $message);
    }

    public function sendInvoice(string $phoneNumber, array $details)
    {
        if (isset($this->templates['payment_invoice'])) {
            return $this->sendByName('payment_invoice', $phoneNumber, [
                "1" => $details['customer_name'],
                "2" => $details['amount'],
                "3" => $details['bill_no']
            ]);
        }

        $message = "Invoice for {$details['customer_name']}\nAmount: ₹{$details['amount']}\nBill No: {$details['bill_no']}";
        return $this->sendMessage($phoneNumber, 'payment_invoice', $message);
    }

    /**
     * Send a general notification by name.
     */
    public function sendNotification(string $phoneNumber, string $templateName, array $variables = [])
    {
        return $this->sendByName($templateName, $phoneNumber, $variables);
    }

    /**
     * Send a WhatsApp message using a pre-defined template name from config.
     * 
     * @param string $name Template name (key in config/services.php twilio.templates)
     * @param string $phoneNumber
     * @param array $variables Template variables {"1": "val1"}
     */
    public function sendByName(string $name, string $phoneNumber, array $variables = [])
    {
        $contentSid = $this->templates[$name] ?? null;

        if (!$contentSid) {
            Log::error("WhatsApp template '{$name}' not found in configuration.");
            return false;
        }

        return $this->sendTemplate($phoneNumber, $contentSid, $variables, $name);
    }

    /**
     * Send a WhatsApp message using a Twilio Content API template (ContentSid).
     *
     * @param string $phoneNumber
     * @param string $contentSid The SID of the Twilio Content template (e.g., HX...)
     * @param array $contentVariables Key-value pairs matching variables in the template (e.g., {"1": "Val1"})
     * @param string $type Optional logging type
     */
    public function sendTemplate(string $phoneNumber, string $contentSid, array $contentVariables = [], string $type = 'template')
    {
        return $this->sendMessage($phoneNumber, $type, null, [
            'contentSid' => $contentSid,
            'contentVariables' => json_encode((object)$contentVariables) // Ensure it's an object even if empty
        ]);
    }

    protected function sendMessage(string $phoneNumber, string $type, ?string $text = null, array $additionalOptions = [])
    {
        // For template messages, log the SID and variables in the payload
        $payload = $text;
        if (!$text && isset($additionalOptions['contentSid'])) {
            $payload = "[Template: " . $additionalOptions['contentSid'] . "] Vars: " . ($additionalOptions['contentVariables'] ?? '{}');
        }

        // Log attempt before sending
        $log = WhatsAppLog::create([
            'phone_number' => $phoneNumber,
            'message_type' => $type,
            'payload' => $payload,
            'status' => 'sending',
        ]);

        if (!$this->client || !$this->from) {
            Log::warning('Twilio credentials missing. Message logged but not sent.', [
                'to' => $phoneNumber, 
                'text' => $text,
                'additional' => $additionalOptions
            ]);
            $log->update(['status' => 'simulated_success']);
            return true; // Return true to allow flow to continue in dev
        }

        try {
            // Ensure phone number has whatsapp: prefix and no extra quotes/spaces
            $target = trim($phoneNumber, " \"'");
            $to = str_starts_with($target, 'whatsapp:') ? $target : "whatsapp:{$target}";

            $options = array_merge([
                'from' => $this->from,
            ], $additionalOptions);

            if ($text) {
                $options['body'] = $text;
            }

            $message = $this->client->messages->create($to, $options);

            if ($message->sid) {
                $log->update(['status' => 'sent', 'message_id' => $message->sid]); // Assuming message_id column exists or just status update
                return true;
            } else {
                $log->update(['status' => 'failed']);
                Log::error('Twilio Error: No SID returned');
                return false;
            }
        } catch (\Exception $e) {
            $log->update(['status' => 'failed']);
            Log::error('Twilio WhatsApp Error: ' . $e->getMessage(), [
                'exception' => $e,
                'to' => $phoneNumber,
                'type' => $type,
                'options' => $additionalOptions
            ]);
            return false;
        }
    }
}
