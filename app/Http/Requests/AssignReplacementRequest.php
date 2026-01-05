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
}
