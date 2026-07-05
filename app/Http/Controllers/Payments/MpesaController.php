<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\CartService;
use App\Services\MpesaService;

class MpesaController extends Controller
{
    protected OrderService $orderService;
    protected CartService $cartService;
    protected MpesaService $mpesaService;

    public function __construct(
        OrderService $orderService,
        CartService $cartService,
        MpesaService $mpesaService
    ) {
        $this->orderService = $orderService;
        $this->cartService = $cartService;
        $this->mpesaService = $mpesaService;
    }

    public function index()
    {
        // Check if user has items in cart
        $cartItems = $this->cartService->getCartItems(Auth::user());

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('toast', [
                'type' => 'warning',
                'message' => 'Your cart is empty. Please add items before checking out.'
            ]);
        }

        if (!session()->has('checkout_data')) {
            return redirect()->route('checkout.index')->with('toast', [
                'type' => 'error',
                'message' => "Please complete checkout first!"
            ]);
        }

        $checkout_data = session('checkout_data');

        // Get current cart items count for display
        $cartItemCount = count($cartItems);

        return inertia('guest/sales/MpesaPayment', [
            'order_total' => $checkout_data['total'] ?? 0,
            'order_subtotal' => $checkout_data['subtotal'] ?? 0,
            'shipping_cost' => $checkout_data['shipping_cost'] ?? 0,
            'contact_phone' => $checkout_data['phone'] ?? null,
            'delivery_method' => $checkout_data['delivery_method'] ?? 'shop',
            'cart_items_count' => $cartItemCount,
            'checkout_data' => $checkout_data
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^254[0-9]{9}$/']
        ], [
            'phone.required' => 'Please enter your MPesa number.',
            'phone.regex' => 'Invalid phone number format. Use 254 followed by 9 digits (e.g., 254712345678).'
        ]);

        // Get checkout data from session
        if (!session()->has('checkout_data')) {
            return redirect()->route('checkout.index')->with('toast', [
                'type' => 'error',
                'message' => 'Checkout data not found. Please start over.'
            ]);
        }

        $checkout_data = session('checkout_data');

        // Get current cart items
        $cartItems = $this->cartService->getCartItems(Auth::user());

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('toast', [
                'type' => 'error',
                'message' => 'Your cart is empty. Cannot process payment.'
            ]);
        }

        // Verify stock availability
        $stockIssues = $this->cartService->verifyStockAvailability($cartItems);
        if (!empty($stockIssues)) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Some items are out of stock. Please update your cart.',
                'details' => $stockIssues
            ]);
        }

        // USE THE TOTALS FROM CHECKOUT_DATA - NO RECALCULATION NEEDED
        // The frontend already calculated these correctly from the live cart
        $amount = $checkout_data['total'];

        try {
            // Create the order using checkout_data and cart items
            $order = $this->orderService->createOrder(
                $checkout_data,
                $cartItems,
                Auth::id()
            );

            \Illuminate\Support\Facades\Log::info('Order created successfully', ['order_id' => $order->id, 'order_number' => $order->order_number]);

            // Store order ID in session
            session(['pending_order_id' => $order->id]);

            // Proceed with M-Pesa payment
            $phoneNumber = $request->phone;
            $accountReference = substr($order->order_number, -12);

            // Initiate STK Push
            $response = $this->mpesaService->stkPush(
                phoneNumber: $phoneNumber,
                amount: $amount,
                accountReference: $accountReference,
                transactionDesc: "Payment for {$order->order_number}"
            );

            \Illuminate\Support\Facades\Log::info('STK Push response', ['response' => $response]);

            if (!$response['success']) {
                \Illuminate\Support\Facades\Log::warning('STK Push failed', ['response' => $response]);
                // Delete the order since payment failed to initiate
                $order->delete();
                session()->forget('pending_order_id');

                return back()->with('toast', [
                    'type' => 'error',
                    'message' => $response['message']
                ]);
            }

            \Illuminate\Support\Facades\Log::info('STK Push successful, creating payment record', [
                'checkout_request_id' => $response['data']['checkout_request_id']
            ]);

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'transaction_id' => $response['data']['checkout_request_id'],
                'amount' => $amount,
                'payment_method' => 0, // mpesa
                'payment_status' => 0, // pending
                'gateway_response' => $response['data'],
            ]);

            \Illuminate\Support\Facades\Log::info('Payment record created', ['payment_id' => $payment->id]);

            // Clear the user's cart AFTER successful payment initiation
            $this->cartService->clearCartForUser(Auth::user());

            // Clear checkout session
            session()->forget('checkout_data');

            // Store payment info in session
            session([
                'mpesa_checkout_request_id' => $response['data']['checkout_request_id'],
                'mpesa_payment_id' => $payment->id,
                'pending_order_id' => $order->id
            ]);

            return redirect()->route('payment.mpesa.status', ['order' => $order->uuid])->with('toast', [
                'type' => 'success',
                'message' => 'STK Push sent! Please check your phone and enter your PIN.'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order creation failed: ' . $e->getMessage());

            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Failed to process payment. Please try again.'
            ]);
        }
    }

    public function status(Order $order)
    {
        if ($order->customer_id !== Auth::id()) {
            abort(403);
        }

        $payment = Payment::where('order_id', $order->id)
            ->where('payment_status', 0)
            ->first();

        $checkoutRequestId = $payment ? $payment->transaction_id : null;

        return inertia('guest/sales/MpesaStatus', [
            'order' => $order,
            'order_uuid' => $order->uuid,
            'checkout_request_id' => $checkoutRequestId,
            'payment_id' => $payment ? $payment->id : null,
        ]);
    }

public function queryStatus(Request $request, Order $order)
{
    if ($order->customer_id !== Auth::id()) {
        abort(403);
    }

    $payment = Payment::where('order_id', $order->id)->first();

    \Illuminate\Support\Facades\Log::info('Query Status Check', [
        'order_id' => $order->id,
        'payment_exists' => $payment ? true : false,
        'payment_status' => $payment ? $payment->payment_status->value : null,
        'order_payment_status' => $order->payment_status->value ?? null,
    ]);

    if (!$payment) {
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Payment record not found'
        ]);
    }

    // Check payment status from database
    $paymentStatus = $payment->payment_status->value;

    if ($paymentStatus === 1) {
        return response()->json([
            'success' => true,
            'status' => 'completed',
            'message' => 'Payment successful!'
        ]);
    }

    if ($paymentStatus === 2) {
        $isCancelled = str_contains($payment->failure_reason ?? '', 'cancelled');
        return response()->json([
            'success' => false,
            'status' => $isCancelled ? 'cancelled' : 'failed',
            'message' => $payment->failure_reason ?? 'Payment failed or was cancelled'
        ]);
    }

    // Still pending - query M-Pesa API as fallback
    if ($paymentStatus === 0 && $payment->transaction_id) {
        try {
            $mpesa_response = $this->mpesaService->queryStatus($payment->transaction_id);
            
            \Illuminate\Support\Facades\Log::info('M-Pesa Query Status Result', [
                'checkout_request_id' => $payment->transaction_id,
                'response' => $mpesa_response
            ]);

            if ($mpesa_response['success'] && isset($mpesa_response['result_code'])) {
                $resultCode = $mpesa_response['result_code'];

                if ($resultCode === '0') {
                    // Payment successful - update records
                    $payment->update(['payment_status' => 1]);
                    $order->update([
                        'payment_status' => 1,
                        'paid_at' => now(),
                        'order_status' => 1,
                    ]);

                    return response()->json([
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'Payment successful!'
                    ]);
                } elseif ($resultCode === '1032' || $resultCode === '1037') {
                    // User cancelled
                    $payment->update([
                        'payment_status' => 2,
                        'failure_reason' => 'User cancelled the transaction'
                    ]);

                    return response()->json([
                        'success' => false,
                        'status' => 'cancelled',
                        'message' => 'Payment was cancelled'
                    ]);
                } elseif ($resultCode !== '0' && $resultCode !== '1032' && $resultCode !== '1037') {
                    // Other errors - keep pending
                    return response()->json([
                        'success' => false,
                        'status' => 'pending',
                        'message' => 'Waiting for payment confirmation...'
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('M-Pesa Query Exception', [
                'error' => $e->getMessage(),
                'checkout_request_id' => $payment->transaction_id
            ]);
        }
    }

    return response()->json([
        'success' => false,
        'status' => 'pending',
        'message' => 'Waiting for payment confirmation...'
    ]);
}

    public function callback(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('=== M-PESA CALLBACK HIT ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'content' => $request->getContent(),
            'ip' => $request->ip(),
        ]);

        \Illuminate\Support\Facades\Log::info('M-Pesa STK Push Callback Received', $request->all());

        $callbackData = $request->all();

        if (!isset($callbackData['Body']['stkCallback'])) {
            return response()->json(['message' => 'Invalid callback data'], 400);
        }

        $stkCallback = $callbackData['Body']['stkCallback'];
        $checkoutRequestId = $stkCallback['CheckoutRequestID'];
        $resultCode = $stkCallback['ResultCode'];
        // Safely get ResultDesc - it might not exist for successful transactions
        $resultDesc = $stkCallback['ResultDesc'] ?? ($resultCode === 0 ? 'Success' : 'Unknown error');

        // Find payment by transaction_id (which is checkout_request_id)
        $payment = Payment::where('transaction_id', $checkoutRequestId)->first();

        if (!$payment) {
            \Illuminate\Support\Facades\Log::error('M-Pesa Callback: Payment not found', [
                'checkout_request_id' => $checkoutRequestId
            ]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $order = $payment->order;

        if ($resultCode === 0) {
            // Successful payment
            $metadata = [];
            if (isset($stkCallback['CallbackMetadata']['Item'])) {
                foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
                    $metadata[$item['Name']] = $item['Value'];
                }
            }

            // Update payment
            $payment->update([
                'payment_status' => 1, // paid
                'gateway_response' => array_merge($payment->gateway_response ?? [], $metadata),
            ]);

            // Update order
            $order->update([
                'payment_status' => 1, // paid
                'paid_at' => now(),
                'order_status' => 1, // processing
                'payment_snapshot' => [
                    'method' => 'mpesa',
                    'transaction_id' => $metadata['MpesaReceiptNumber'] ?? $checkoutRequestId,
                    'amount' => $metadata['Amount'] ?? $payment->amount,
                    'phone' => $metadata['PhoneNumber'] ?? null,
                ]
            ]);

            \Illuminate\Support\Facades\Log::info('M-Pesa Callback: Payment successful', [
                'order_id' => $order->id,
                'receipt_number' => $metadata['MpesaReceiptNumber'] ?? null
            ]);

        } elseif ($resultCode === 1032) {
            // User cancelled
            $payment->update([
                'payment_status' => 2, // cancelled
                'failure_reason' => 'User cancelled the transaction'
            ]);

            \Illuminate\Support\Facades\Log::info('M-Pesa Callback: Payment cancelled by user', [
                'order_id' => $order->id
            ]);

                // Also update order status
            $order->update([
                'payment_status' => 2, // cancelled
                'cancelled_at' => now(),
                'order_status' => 5, // cancelled - adjust based on your enum
            ]);

            \Illuminate\Support\Facades\Log::info('M-Pesa Callback: Payment cancelled by user', [
                'order_id' => $order->id
            ]);

        } else {
            // Failed payment
            $payment->update([
                'payment_status' => 2, // failed/cancelled
                'failure_reason' => $resultDesc
            ]);

            \Illuminate\Support\Facades\Log::warning('M-Pesa Callback: Payment failed', [
                'order_id' => $order->id,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc
            ]);
        }

        return response()->json(['message' => 'Callback processed successfully']);
    }
}
