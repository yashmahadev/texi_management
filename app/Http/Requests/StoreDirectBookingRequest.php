<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDirectBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_direct_bookings');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_mobile' => 'required|string|max:15',
            'pickup_location' => 'required|string',
            'drop_location' => 'required|string',
            'booking_datetime' => 'required|date|after:now',
            'booking_end_datetime' => 'nullable|date|after_or_equal:booking_datetime',
            'estimated_km' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'admin_notes' => 'nullable|string',
        ];
    }
}
