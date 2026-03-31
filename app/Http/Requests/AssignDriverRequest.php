<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign_drivers_to_bookings');
    }

    public function rules(): array
    {
        return [
            'driver_id'  => 'required|exists:drivers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.required'  => 'Please select a driver to assign.',
            'driver_id.exists'    => 'The selected driver does not exist.',
            'vehicle_id.required' => 'Please select a vehicle to assign.',
            'vehicle_id.exists'   => 'The selected vehicle does not exist.',
        ];
    }
}
