<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryLocation;
use App\Http\Requests\Sales\CheckoutRequest;

class CheckoutController extends Controller
{
    public function index()
    {
        $delivery_locations = DeliveryLocation::orderBy('name')->get();

        return inertia('guest/sales/Checkout', [
            'delivery_locations' => $delivery_locations
        ]);
    }

    public function store(CheckoutRequest $request)
    {
        $checkout_data = $request->validated();

        // Add the totals from the request (these come from the frontend)
        $checkout_data['subtotal'] = $request->input('subtotal');
        $checkout_data['shipping_cost'] = $request->input('shipping_cost', 0);
        $checkout_data['total'] = $request->input('total');

        session(['checkout_data' => $checkout_data]);

        // Redirect based on payment method
        if ($request->payment_method === 'mpesa') {
            return redirect()->route('payment.mpesa');
        } else {
            return redirect()->route('payment.stripe');
        }

        // dd($request);
    }
}
