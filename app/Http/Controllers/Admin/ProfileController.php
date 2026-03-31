<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        return view('admin.profile.index', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'             => 'required|string|min:2|max:255',
            'email'            => 'required|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'nullable|string',
            'password'         => 'nullable|string|min:8|confirmed',
        ], [
            'name.required'          => 'Your name is required.',
            'name.min'               => 'Name must be at least 2 characters.',
            'email.required'         => 'Email address is required.',
            'email.email'            => 'Please enter a valid email address.',
            'email.unique'           => 'This email address is already used by another account.',
            'password.min'           => 'New password must be at least 8 characters.',
            'password.confirmed'     => 'New password and confirmation do not match.',
        ]);

        // If changing password, verify current one first
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $user->password = Hash::make($request->password);
        }

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
