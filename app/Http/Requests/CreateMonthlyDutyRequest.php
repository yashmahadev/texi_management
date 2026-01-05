<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMonthlyDutyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth is handled by route middleware + Policy
    }

    public function rules(): array
    {
        return [
            'department_name' => 'required|string|max:255',
            'officer_name' => 'required|string|max:255',
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'expected_start_time' => 'required',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
        ];
    }
}
