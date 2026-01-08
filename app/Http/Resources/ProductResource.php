<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'quantity_on_hand' => $this->quantity_on_hand,
            'reorder_level' => $this->reorder_level,
            'status' => $this->status,
            'margin_percentage' => round($this->margin, 2),
            'is_low_stock' => $this->isLowStock(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
