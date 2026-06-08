<?php

namespace App\Http\Resources\Community;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "content" => $this->content,
            "image" => $this->image_url,
            "likes_count" => $this->likes_count,
            "comments_count" => $this->comments_count,
            "is_pinned" => $this->is_pinned,
            "pinned_at" => $this->pinned_at,
            "published_at" => $this->published_at->diffForHumans(),
            "scheduled_for" => $this->scheduled_for,
            "is_draft" => $this->is_draft,
            "shop_id" => $this->shop_id,
            "created_at" => $this->created_at,
            "shop_name" => $this->shop->name,
            "shop_category_name" => $this->shop->category_name,
            "shop_logo_url" => $this->shop->logo_url,
        ];
    }
}
