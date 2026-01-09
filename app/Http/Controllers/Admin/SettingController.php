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

        foreach ($data as $key => $value) {
            if ($key !== 'company_logo') {
                Setting::set($key, $value);
            }
        }

        return back()->with('success', 'Company profile updated successfully.');
    }
}
