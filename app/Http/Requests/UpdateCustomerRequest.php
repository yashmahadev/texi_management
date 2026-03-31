<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_customers');
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')->id;

        return [
            'name'    => 'required|string|min:2|max:255',
            'mobile'  => ['required', 'string', 'regex:/^[6-9]\d{9}$/', "unique:customers,mobile,{$customerId}"],
            'email'   => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes'   => 'nullable|string|max:1000',
            'status'  => 'nullable|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Customer name is required.',
            'name.min'         => 'Customer name must be at least 2 characters.',
            'mobile.required'  => 'Mobile number is required.',
            'mobile.regex'     => 'Please enter a valid 10-digit Indian mobile number (starting with 6–9).',
            'mobile.unique'    => 'This mobile number is already registered to another customer.',
            'email.email'      => 'Please enter a valid email address.',
            'status.in'        => 'Status must be either active or inactive.',
        ];
    }
}
