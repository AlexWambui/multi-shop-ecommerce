<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StripeController extends Controller
{
    public function index()
    {
        // Check if checkout data exists
        if (!session()->has('checkout_data')) {

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Please complete checkout first!"
            ]);

            return to_route('checkout.index');
        }

        $checkout_data = session('checkout_data');

        $total = 0;

        return inertia('guest/sales/StripePayment', [
            'order_total' => $total
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'card_number' => 'required|string',
            'expiry_date' => 'required|string',
            'cvv' => 'required|string',
            'card_name' => 'required|string',
        ]);

        $checkoutData = session('checkout_data');

        // Here you would integrate with Stripe API
        // For now, we'll just dd to verify
        dd([
            'checkout_data' => $checkoutData,
            'payment_details' => $request->only(['card_number', 'expiry_date', 'cvv', 'card_name']),
            'message' => 'Stripe payment would be processed here'
        ]);

        // Clear checkout session after successful payment
        session()->forget('checkout_data');

        return redirect()->route('orders.index')->with('success', 'Payment successful!');
    }
}
