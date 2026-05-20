<?php

namespace App\Http\Responses;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(private readonly CartService $cartService) {}

    public function toResponse($request)
    {
        // Get the guest cart ID stored BEFORE login
        $guestCartId = session('guest_cart_id');

        Log::info('LoginResponse - attempting merge', [
            'guest_cart_id' => $guestCartId,
            'user_id' => $request->user()?->id
        ]);

        if ($guestCartId) {
            $guestCart = Cart::with('items')->find($guestCartId);

            if ($guestCart && $guestCart->items()->exists()) {
                Log::info('Found guest cart with items', [
                    'cart_id' => $guestCart->id,
                    'item_count' => $guestCart->items->count()
                ]);

                $this->cartService->mergeCarts($guestCart, $request->user());
                Log::info('Merge completed successfully');
            }

            // Clean up session
            session()->forget('guest_cart_id');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
