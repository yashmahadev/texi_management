<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignReplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'replacement_driver_id' => 'required|exists:drivers,id',
            'reason' => 'required|string|min:5',
        ];
    }
    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if replacement driver is same as current driver
            $log = $this->route('log'); // Assumes route param is 'log'
            if ($log && $this->replacement_driver_id == $log->monthlyDuty->primary_driver_id) {
                $validator->errors()->add('replacement_driver_id', 'Replacement driver cannot be the same as the primary driver.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'replacement_driver_id.required' => 'Please select a driver to replace the current one.',
            'replacement_driver_id.exists' => 'The selected replacement driver is invalid.',
            'reason.required' => 'Please provide a reason for the replacement.',
            'reason.min' => 'The reason must be at least 5 characters long.',
        ];
    }
}
