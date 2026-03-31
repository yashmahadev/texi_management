<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustFareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('adjust_booking_fares');
    }

    public function rules(): array
    {
        return [
            'adjusted_fare'      => 'required|numeric|min:0|max:999999',
            'adjustment_reason'  => 'required|string|min:5|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'adjusted_fare.required'     => 'Please enter the adjusted fare amount.',
            'adjusted_fare.numeric'      => 'Fare must be a valid number.',
            'adjusted_fare.min'          => 'Fare cannot be negative.',
            'adjusted_fare.max'          => 'Fare amount seems too high. Please verify.',
            'adjustment_reason.required' => 'Please provide a reason for the fare adjustment.',
            'adjustment_reason.min'      => 'Adjustment reason must be at least 5 characters.',
            'adjustment_reason.max'      => 'Adjustment reason cannot exceed 500 characters.',
        ];
    }
}
