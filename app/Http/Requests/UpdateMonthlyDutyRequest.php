<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonthlyDutyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller via $this->authorize()
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $monthlyDuty = $this->route('monthly_duty');
        
        return [
            'group'               => 'required|in:Government,Corporate',
            'department_id'       => 'required|exists:departments,id',
            'officer_name'        => 'required|string|min:2|max:255',
            'vehicle_id'          => [
                'required',
                'exists:vehicles,id',
                function ($attribute, $value, $fail) use ($monthlyDuty) {
                    $startDate = $this->input('start_date');
                    $endDate   = $this->input('end_date');
                    if ($startDate && $endDate) {
                        $overlap = \App\Models\MonthlyDuty::where('vehicle_id', $value)
                            ->where('id', '!=', $monthlyDuty->id) // exclude current duty
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
            'group.required'                  => 'Please select a group.',
            'group.in'                        => 'Group must be Government or Corporate.',
            'department_id.required'          => 'Please select a department.',
            'department_id.exists'            => 'The selected department is invalid.',
            'officer_name.required'           => 'Officer name is required.',
            'officer_name.min'                => 'Officer name must be at least 2 characters.',
            'vehicle_id.required'             => 'Please select a vehicle.',
            'vehicle_id.exists'               => 'The selected vehicle does not exist.',
            'start_date.required'             => 'Start date is required.',
            'end_date.required'               => 'End date is required.',
            'end_date.after_or_equal'         => 'End date must be on or after the start date.',
            'expected_start_time.required'    => 'Expected start time is required.',
            'expected_start_time.date_format' => 'Start time must be in HH:MM format.',
            'expected_end_time.date_format'   => 'End time must be in HH:MM format.',
            'pincode.digits'                  => 'Pincode must be exactly 6 digits.',
        ];
    }
}
