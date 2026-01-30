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
            'group' => 'required|in:Government,Corporate',
            'department_id' => 'required|exists:departments,id',
            'department_name' => 'nullable|string|max:255',
            'officer_name' => 'required|string|max:255',
            'vehicle_id' => [
                'required',
                'exists:vehicles,id',
                function ($attribute, $value, $fail) {
                    $startDate = $this->input('start_date');
                    $endDate = $this->input('end_date');

                    if ($startDate && $endDate) {
                        $overlap = \App\Models\MonthlyDuty::where('vehicle_id', $value)
                            ->where(function ($query) use ($startDate, $endDate) {
                                $query->whereBetween('start_date', [$startDate, $endDate])
                                      ->orWhereBetween('end_date', [$startDate, $endDate])
                                      ->orWhere(function ($q) use ($startDate, $endDate) {
                                          $q->where('start_date', '<=', $startDate)
                                            ->where('end_date', '>=', $endDate);
                                      });
                            })->exists();

                        if ($overlap) {
                            $fail('This vehicle is already assigned to another duty during the selected period.');
                        }
                    }
                },
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'expected_start_time' => 'required',
            'expected_end_time' => 'nullable',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'Please select a department.',
            'department_id.exists' => 'The selected department is invalid or has been removed.',
            'vehicle_id.required' => 'Please select a vehicle for this duty.',
            'vehicle_id.exists' => 'The selected vehicle no longer exists in our records.',
            'start_date.required' => 'A start date is required for the duty.',
            'end_date.required' => 'An end date is required for the duty.',
            'end_date.after_or_equal' => 'The end date must be the same as or after the start date.',
            'officer_name.required' => 'Please enter the name of the reporting officer.',
        ];
    }
}
