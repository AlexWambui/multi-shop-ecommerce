<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function orderDetailsPage(Order $order)
    {
        return inertia('guest/sales/OrderDetails', [
            'order' => $order
        ]);
    }
}
