<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDirectBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_direct_bookings');
    }

    public function rules(): array
    {
        return [
            'customer_id'          => 'nullable|exists:customers,id',
            'customer_name'        => 'required|string|min:2|max:255',
            'customer_mobile'      => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'pickup_location'      => 'required|string|max:500',
            'drop_location'        => 'required|string|max:500',
            'booking_datetime'     => 'required|date',
            'booking_end_datetime' => 'nullable|date|after_or_equal:booking_datetime',
            'estimated_km'         => 'nullable|integer|min:1|max:10000',
            'base_fare'            => 'nullable|numeric|min:0|max:99999',
            'per_km_rate'          => 'nullable|numeric|min:0|max:9999',
            'notes'                => 'nullable|string|max:1000',
            'route_remarks'        => 'nullable|string|max:1000',
            'admin_notes'          => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'        => 'Customer name is required.',
            'customer_name.min'             => 'Customer name must be at least 2 characters.',
            'customer_mobile.required'      => 'Customer mobile number is required.',
            'customer_mobile.regex'         => 'Please enter a valid 10-digit Indian mobile number (starting with 6–9).',
            'pickup_location.required'      => 'Pickup location is required.',
            'drop_location.required'        => 'Drop location is required.',
            'booking_datetime.required'     => 'Booking date and time is required.',
            'booking_end_datetime.after_or_equal' => 'Booking end time must be after or equal to the start time.',
            'estimated_km.min'              => 'Estimated KM must be at least 1.',
            'base_fare.min'                 => 'Base fare cannot be negative.',
            'per_km_rate.min'               => 'Per KM rate cannot be negative.',
        ];
    }
}
