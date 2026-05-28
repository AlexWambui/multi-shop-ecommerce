<?php

namespace App\Http\Resources\Orders;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'total_amount' => $this->total_amount,
            'order_status' => $this->order_status,
            'order_status_label' => $this->order_status_label,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'created_at' => $this->created_at
                ->setTimezone('Africa/Nairobi')
                ->format('d M, Y g:i A') . ' (' .
                $this->created_at->setTimezone('Africa/Nairobi')->diffForHumans() . ')',
                // Output: "Jan 15, 2024 5:30 PM (2 hours ago)"
        ];
    }
}
