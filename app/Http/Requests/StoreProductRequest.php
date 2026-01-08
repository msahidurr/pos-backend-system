<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isManager();
    }

    public function rules(): array
    {
        return [
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'cost_price' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'selling_price' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'quantity_on_hand' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU already exists.',
            'cost_price.regex' => 'Cost price must be a valid decimal value.',
            'selling_price.regex' => 'Selling price must be a valid decimal value.',
        ];
    }
}
