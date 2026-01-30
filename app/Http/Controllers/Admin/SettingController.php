<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'company_name' => Setting::get('company_name'),
            'company_address' => Setting::get('company_address'),
            'company_mobile' => Setting::get('company_mobile'),
            'company_email' => Setting::get('company_email'),
            'company_logo' => Setting::get('company_logo'),
            'notification_sound' => Setting::get('notification_sound'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_mobile' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'notification_sound' => 'nullable|file|mimes:mp3,wav,ogg|max:2048',
        ], [
            'company_email.email' => 'Please enter a valid company email address.',
            'company_logo.image' => 'The logo must be an image file (JPEG, PNG, JPG, SVG).',
            'company_logo.max' => 'The logo size must be less than 2MB.',
            'notification_sound.mimes' => 'The notification sound must be an MP3, WAV or OGG file.',
        ]);

        if ($request->hasFile('company_logo')) {
            // Delete old logo if exists
            $oldLogo = Setting::get('company_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('company_logo')->store('company', 'public');
            Setting::set('company_logo', $path);
        }

        if ($request->hasFile('notification_sound')) {
            // Delete old sound if exists
            $oldSound = Setting::get('notification_sound');
            if ($oldSound && $oldSound !== 'default') {
                Storage::disk('public')->delete($oldSound);
            }

            $path = $request->file('notification_sound')->store('sounds', 'public');
            Setting::set('notification_sound', $path);
        }

        foreach ($data as $key => $value) {
            if ($key !== 'company_logo' && $key !== 'notification_sound') {
                Setting::set($key, $value);
            }
        }

        return back()->with('success', 'Company profile updated successfully.');
    }
}
