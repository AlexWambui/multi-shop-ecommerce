<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MpesaController extends Controller
{
    public function index()
    {
        if (!session()->has('checkout_data')) {

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Please complete checkout first!"
            ]);

            return to_route('checkout.index');
        }

        $checkout_data = session('checkout_data');

        return inertia('guest/sales/MpesaPayment', [
            'order_total' => $checkout_data['total'] ?? 0,
            'contact_phone' => $checkout_data['phone_number'] ?? null,
            'checkout_data' => $checkout_data
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^254(7|1)[0-9]{8}$/']
        ], [
            'phone_number.required' => 'Please enter your MPesa number.',
            'phone_number.regex' => 'Invalid phone number format. Use 254 followed by 9 digits (e.g., 254712345678 for Safaricom or 254112345678 for Airtel).'
        ]);

        $checkout_data = session('checkout_data');

        // - $checkout_data['name']
        // - $checkout_data['email']
        // - $checkout_data['phone_number']
        // - $checkout_data['delivery_method']
        // - $checkout_data['payment_method']
        // - $checkout_data['extra_details']
        // - $checkout_data['delivery_location_id']
        // - $checkout_data['delivery_area_id']
        // - $checkout_data['subtotal']
        // - $checkout_data['shipping_cost']
        // - $checkout_data['total']

        // TODO: Integrate Mpesa API for STK Push
        dd([
            'checkout_data' => $checkout_data,
            'payment_phone' => $request->phone_number,
            'message' => 'STK push would be sent here'
        ]);

        // Clear checkout session after successful payment
        session()->forget('checkout_data');

        return redirect()->route('orders.index')->with('success', 'Payment successful!');
    }
}
