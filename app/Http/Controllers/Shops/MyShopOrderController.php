<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use App\Http\Resources\Orders\OrderResource;

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
}
