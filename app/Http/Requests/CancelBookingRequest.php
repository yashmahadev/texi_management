<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel_bookings');
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Please provide a reason for cancellation.',
            'cancellation_reason.min'      => 'Cancellation reason must be at least 10 characters.',
            'cancellation_reason.max'      => 'Cancellation reason cannot exceed 500 characters.',
        ];
    }
}
