<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Exception;
use App\Models\Order;
use App\Models\Shop;
use App\Enums\OrderStatus;
use App\Http\Resources\Orders\OrderResource;
use App\Http\Resources\Orders\EditOrderResource;

class MyShopOrderController extends Controller
{

    public function index(Shop $shop)
    {
        $orders = $shop->orders()->latest()->paginate(50);

        return inertia('app/shops/my-shops/orders/Index', [
            'orders' => OrderResource::collection($orders),
            'shop' => $shop
        ]);
    }

    public function edit(Shop $shop, Order $order)
    {
        $order_statuses = OrderStatus::options();

        return inertia('app/shops/my-shops/orders/Edit', [
            'order' => new EditOrderResource($order),
            'shop' => $shop,
            'order_statuses' => $order_statuses
        ]);
    }

    public function update(Shop $shop, Order $order, Request $request)
    {
        try {
            $validated_data = $request->validate([
                'order_status' => ['required', 'integer', 'in:' . implode(',', array_column(OrderStatus::cases(), 'value'))],
                'notes' => ['nullable', 'string']
            ]);

            $order->update($validated_data);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Order updated successfully"
            ]);

            return to_route('my-shops.orders.index', $shop->slug);
        } catch (Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to update order: ' . {$e->getMessage()}"
            ]);

            return back();
        }
    }

    public function destroy(Shop $shop, Order $order)
    {
        $order->delete();

        Inertia::flash('toast', [
            'type' => "success",
            'message' => "Order deleted successfully"
        ]);

        return to_route('my-shops.orders.index', $shop->slug);
    }
}
