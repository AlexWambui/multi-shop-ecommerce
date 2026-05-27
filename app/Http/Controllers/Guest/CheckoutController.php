<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryLocation;
use App\Http\Requests\Sales\CheckoutRequest;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        // Check if user has items in cart before showing checkout page
        $cartItems = $this->cartService->getCartItems(Auth::user());

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('toast', [
                'type' => 'warning',
                'message' => 'Your cart is empty. Please add items before checking out.'
            ]);
        }

        $delivery_locations = DeliveryLocation::where('is_active', true)
            ->orderBy('name')
            ->get();

        return inertia('guest/sales/Checkout', [
            'delivery_locations' => $delivery_locations
        ]);
    }

    public function store(CheckoutRequest $request)
    {
        // Check again when submitting (in case cart changed between page load and submission)
        $cartItems = $this->cartService->getCartItems(Auth::user());

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('toast', [
                'type' => 'error',
                'message' => 'Your cart is empty. Cannot proceed with checkout.'
            ]);
        }

        $checkout_data = $request->validated();

        // Add the totals from the request (these come from the frontend)
        $checkout_data['subtotal'] = $request->input('subtotal');
        $checkout_data['shipping_cost'] = $request->input('shipping_cost', 0);
        $checkout_data['total'] = $request->input('total');

        // Store checkout data in session
        session(['checkout_data' => $checkout_data]);

        // Redirect based on payment method
        if ($request->payment_method === 'mpesa') {
            return redirect()->route('payment.mpesa');
        } else {
            return redirect()->route('payment.stripe');
        }
    }
}
