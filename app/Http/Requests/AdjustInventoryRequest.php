<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isManager();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'adjustment_quantity' => 'required|integer|not_in:0',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_quantity.not_in' => 'Adjustment quantity cannot be zero.',
        ];
    }
}
