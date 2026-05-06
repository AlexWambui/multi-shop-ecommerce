<?php

namespace App\Http\Resources\Shops;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopDetailsResource extends JsonResource
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
            'custom_slug' => $this->custom_slug ?? 'not-customized',
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'cover_url' => $this->cover_url,
            'category_name' => $this->category_name,
            'owner_name' => $this->owner_name,
            'owner_joined_at' => $this->owner->created_at->format('d-m-Y'),
            'is_active' => (bool) $this->is_active
        ];
    }
}
