<?php

namespace App\Http\Resources\Products;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'thumbnail_url' => $this->thumbnail_url,
            'category_name' => $this->category_name,
            'current_stock' => $this->current_stock,
            'track_inventory' => (bool) $this->track_inventory,
            'low_stock_threshold' => $this->low_stock_threshold,
        ];
    }
}
