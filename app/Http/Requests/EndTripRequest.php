<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EndTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('driver')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'actual_km' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ];
    }
}
