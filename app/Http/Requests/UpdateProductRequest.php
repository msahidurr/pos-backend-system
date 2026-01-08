<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isManager();
    }

    public function rules(): array
    {
        return [
            'name' => 'string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'cost_price' => 'numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'selling_price' => 'numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'quantity_on_hand' => 'integer|min:0',
            'reorder_level' => 'integer|min:0',
            'status' => 'in:active,inactive,discontinued',
        ];
    }
}
