<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\DeliveryArea;
use App\Models\User;

class OrderService
{
    /**
     * Create a new order from checkout data
     */
    public function createOrder(array $checkoutData, array $cartItems, int $customerId): Order
    {
        return DB::transaction(function () use ($checkoutData, $cartItems, $customerId) {
            $orderNumber = $this->generateOrderNumber();

            // Get shop_id from first cart item
            $shopId = $cartItems[0]['shop_id'] ?? null;

            if (!$shopId) {
                throw new \Exception('No shop found in cart items');
            }

            // Prepare snapshots
            $customerSnapshot = [
                'name' => $checkoutData['name'],
                'email' => $checkoutData['email'],
                'phone' => $checkoutData['phone'],
            ];

            $deliverySnapshot = [
                'delivery_method' => $checkoutData['delivery_method'],
                'delivery_location_id' => $checkoutData['delivery_location_id'],
                'delivery_area_id' => $checkoutData['delivery_area_id'],
            ];

            $pricingSnapshot = [
                'subtotal' => $checkoutData['subtotal'],
                'shipping_cost' => $checkoutData['shipping_cost'],
                'total' => $checkoutData['total'],
            ];

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'subtotal' => $checkoutData['subtotal'],
                'discount_amount' => 0,
                'shipping_cost' => $checkoutData['shipping_cost'],
                'tax_amount' => 0,
                'total_amount' => $checkoutData['total'],
                'order_status' => 0, // pending
                'payment_method' => $checkoutData['payment_method'] === 'mpesa' ? 0 : 1,
                'payment_status' => 0, // pending
                'notes' => $checkoutData['extra_details'] ?? null,
                'customer_details_snapshot' => $customerSnapshot,
                'delivery_details_snapshot' => $deliverySnapshot,
                'pricing_snapshot' => $pricingSnapshot,
                'shop_id' => $shopId,
                'customer_id' => $customerId,
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => 0,
                    'total_price' => $item['subtotal'],
                    'product_name_snapshot' => $item['product_name'],
                    'product_sku_snapshot' => $item['product_sku'] ?? '',
                ]);
            }

            return $order;
        });
    }

    /**
     * Generate unique order number
     * Format: INV-YYYYMMDD-XXXXX
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $lastOrder = Order::where('order_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = intval(substr($lastOrder->order_number, -5));
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '00001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Get shop ID from cart items (assuming all items from same shop)
     */
    protected function getShopIdFromCart(array $cartItems): int
    {
        // Get the first product's shop_id
        if (!empty($cartItems)) {
            $product = Product::find($cartItems[0]['product_id']);
            if ($product) {
                return $product->shop_id;
            }
        }

        throw new \Exception('No cart items found');
    }
}
