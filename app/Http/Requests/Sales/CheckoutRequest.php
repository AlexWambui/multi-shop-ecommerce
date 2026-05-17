<?php

namespace App\Http\Requests\Sales;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'delivery_method' => 'required|in:shop,delivery',
            'payment_method' => 'required|in:mpesa,stripe',
            'extra_details' => 'nullable|string|max:1000',
            'delivery_location_id' => 'required_if:delivery_method,delivery|nullable|exists:delivery_locations,id',
            'delivery_area_id' => 'required_if:delivery_method,delivery|nullable|exists:delivery_areas,id',
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_location_id.required_if' => 'Please select a delivery location',
            'delivery_area_id.required_if' => 'Please select a delivery area',
        ];
    }
}
