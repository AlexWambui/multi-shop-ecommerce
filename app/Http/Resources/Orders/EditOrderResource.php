<?php

namespace App\Http\Resources\Orders;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditOrderResource extends JsonResource
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
            'customer_name' => $this->customer->name ?? $this->customer_details_snapshot['name'] ?? null,
            'customer_phone_number' => $this->customer->phone ?? $this->customer_details_snapshot['phone'] ?? null,
            'delivery_method' => $this->delivery_details_snapshot['delivery_method'] ?? null,
            'delivery_location' => $this->getDeliveryLocationDisplay(),
            'delivery_area' => $this->getDeliveryAreaDisplay(),
            'delivery_address' => $this->getDeliveryAddressDisplay(),
            'order_items' => $this->orderItems,
            'subtotal' => $this->subtotal,
            'total_amount' => $this->total_amount,
            'order_status' => $this->order_status,
            'order_status_label' => $this->order_status_label,
            'payment_method_label' => $this->payment_method_label,
            'payment_transaction_id' => $this->payment->transaction_id,
            'payment_amount' => $this->payment->amount,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'notes' => $this->notes,
            'created_at' => $this->created_at
                ->setTimezone('Africa/Nairobi')
                ->format('d M, Y g:i A') . ' (' .
                $this->created_at->setTimezone('Africa/Nairobi')->diffForHumans() . ')',
                // Output: "Jan 15, 2024 5:30 PM (2 hours ago)"
        ];
    }

    private function getDeliveryLocationDisplay(): ?string
    {
        $deliveryMethod = $this->delivery_details_snapshot['delivery_method'] ?? null;

        // If pickup from shop
        if ($deliveryMethod === 'shop') {
            return 'Pick up from shop';
        }

        // If delivery with location
        if ($deliveryMethod === 'delivery' && $this->delivery_location) {
            return $this->delivery_location->name;
        }

        // Fallback from snapshot
        return $this->delivery_details_snapshot['delivery_location_name'] ?? null;
    }

    private function getDeliveryAreaDisplay(): ?string
    {
        $deliveryMethod = $this->delivery_details_snapshot['delivery_method'] ?? null;

        // If pickup from shop
        if ($deliveryMethod === 'shop') {
            return 'Pick up from shop';
        }

        // If delivery with area
        if ($deliveryMethod === 'delivery' && $this->delivery_area) {
            return $this->delivery_area->name;
        }

        // Fallback from snapshot
        return $this->delivery_details_snapshot['delivery_area_name'] ?? null;
    }

    private function getDeliveryAddressDisplay(): ?string
    {
        $deliveryMethod = $this->delivery_details_snapshot['delivery_method'] ?? null;

        // If pickup from shop
        if ($deliveryMethod === 'shop') {
            return 'Pick up from shop';
        }

        // If delivery with address
        if ($deliveryMethod === 'delivery' && $this->delivery_address) {
            return $this->delivery_address;
        }

        // Fallback from snapshot
        return $this->delivery_details_snapshot['delivery_address'] ?? null;
    }
}
