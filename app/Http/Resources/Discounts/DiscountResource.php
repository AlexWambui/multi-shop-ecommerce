<?php

namespace App\Http\Resources\Discounts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Discount;

class DiscountResource extends JsonResource
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
            'type_label' => $this->type_label,
            'formatted_value' => $this->formatted_value,
            'scope_label' => $this->scope_label,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
            'starts_at' => $this->starts_at->format('d/m/Y h:i A'),
            'expires_at' => $this->expires_at->format('d/m/Y h:i A'),
            'is_scheduled' => $this->starts_at->isFuture(), // True if starts in future
            'starts_in_days' => $this->starts_at->isFuture() ? $this->starts_at->diffInDays(now()) : null,
            'status' => $this->is_active_now ? 'active' : ($this->starts_at->isFuture() ? 'scheduled' : ($this->is_expired ? 'expired' : 'inactive')),
            'targets_count' => $this->scope === Discount::SCOPE_PRODUCT_CATEGORY 
                ? $this->categories_count 
                : ($this->scope === Discount::SCOPE_SPECIFIC_PRODUCTS 
                    ? $this->products_count 
                    : 0),
        ];
    }
}
