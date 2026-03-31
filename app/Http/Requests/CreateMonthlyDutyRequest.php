<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMonthlyDutyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group'               => 'required|in:Government,Corporate',
            'department_id'       => 'required|exists:departments,id',
            'department_name'     => 'nullable|string|max:255',
            'officer_name'        => 'required|string|min:2|max:255',
            'vehicle_id'          => [
                'required',
                'exists:vehicles,id',
                function ($attribute, $value, $fail) {
                    $startDate = $this->input('start_date');
                    $endDate   = $this->input('end_date');
                    if ($startDate && $endDate) {
                        $overlap = \App\Models\MonthlyDuty::where('vehicle_id', $value)
                            ->where(function ($q) use ($startDate, $endDate) {
                                $q->whereBetween('start_date', [$startDate, $endDate])
                                  ->orWhereBetween('end_date', [$startDate, $endDate])
                                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                                      $q2->where('start_date', '<=', $startDate)
                                         ->where('end_date', '>=', $endDate);
                                  });
                            })->exists();
                        if ($overlap) {
                            $fail('This vehicle is already assigned to another duty during the selected period.');
                        }
                    }
                },
            ],
            'start_date'          => 'required|date',
            'end_date'            => 'required|date|after_or_equal:start_date',
            'expected_start_time' => 'required|date_format:H:i',
            'expected_end_time'   => 'nullable|date_format:H:i',
            'state'               => 'nullable|string|max:100',
            'city'                => 'nullable|string|max:100',
            'pincode'             => 'nullable|digits:6',
            'route_remarks'       => 'nullable|string|max:1000',
            'is_recurring'        => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'group.required'               => 'Please select a group (Government or Corporate).',
            'group.in'                     => 'Group must be either Government or Corporate.',
            'department_id.required'       => 'Please select a department.',
            'department_id.exists'         => 'The selected department is invalid or has been removed.',
            'officer_name.required'        => 'Please enter the name of the reporting officer.',
            'officer_name.min'             => 'Officer name must be at least 2 characters.',
            'vehicle_id.required'          => 'Please select a vehicle for this duty.',
            'vehicle_id.exists'            => 'The selected vehicle no longer exists in our records.',
            'start_date.required'          => 'A start date is required for the duty.',
            'end_date.required'            => 'An end date is required for the duty.',
            'end_date.after_or_equal'      => 'The end date must be the same as or after the start date.',
            'expected_start_time.required' => 'Please enter the expected start time.',
            'expected_start_time.date_format' => 'Expected start time must be in HH:MM format (e.g. 09:00).',
            'expected_end_time.date_format'   => 'Expected end time must be in HH:MM format (e.g. 18:00).',
            'pincode.digits'               => 'Pincode must be exactly 6 digits.',
            'route_remarks.max'            => 'Route/Remarks cannot exceed 1000 characters.',
        ];
    }
}
