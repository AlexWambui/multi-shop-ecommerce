<?php

namespace App\Http\Resources\Products;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailsPageResource extends JsonResource
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
            'has_discount' => $this->has_discount,
            'discount_display' => $this->discount_display,
            'discounted_price' => $this->discounted_price,
            'discount_pct' => $this->discount_pct,
            'discount_starts_at' => $this->best_discount?->starts_at,
            'discount_expires_at' => $this->best_discount?->expires_at,
            'thumbnail_url' => $this->thumbnail_url,
            'category_name' => $this->category_name ?? 'Uncategorized',

            'images' => $this->images->map(fn($image) => [
                'id' => $image->id,
                'full_url' => $image->image_url
            ]),

            'shop' => [
                'id' => $this->shop->id,
                'name' => $this->shop->name,
                'slug' => $this->shop->slug ?? $this->shop->custom_slug,
                'logo_url' => $this->shop->logo_url,
                'is_verified' => (bool) ($this->shop->is_verified ?? false),
            ],
        ];
    }
}
