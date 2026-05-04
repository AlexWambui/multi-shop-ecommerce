<?php

namespace App\Http\Requests\Products;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'sku' => [
                'nullable', 
                'string', 
                'max:100',
                Rule::unique('products', 'sku')->ignore($this->route('product')?->id)
            ],
            'description' => ['nullable', 'string'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'shop_id' => ['required', 'exists:shops,id'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,gif,svg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already in use. Please use a different one.',
            'barcode.unique' => 'This barcode is already in use.',
            'images.max' => 'You can upload a maximum of 5 images.',
            'images.*.max' => 'Each image must be less than 2MB.',
            'images.*.mimes' => 'Only JPG, JPEG, PNG, GIF, SVG and WEBP images are allowed.',
        ];
    }
}
