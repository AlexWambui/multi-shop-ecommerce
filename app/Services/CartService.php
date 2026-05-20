<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;

class CartService
{
    public function getCart($user = null, $session_id = null): Cart
    {
        if ($user) {
            return Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['currency' => 'KES']
            );
        }

        if ($session_id) {
            // Clean expired carts
            $this->cleanExpiredCarts();

            $cart = Cart::where('session_id', $session_id)->first();

            if ($cart && $cart->expires_at && $cart->expires_at->isPast()) {
                $cart->items()->delete();
                $cart->delete();
                $cart = null;
            }

            if (!$cart) {
                $cart = Cart::create([
                    'session_id' => $session_id,
                    'currency' => 'KES',
                    'expires_at' => now()->addHours(4) // Changed to 4 hours
                ]);
            }

            // Store cart ID in session for later retrieval
            session(['guest_cart_id' => $cart->id]);

            return $cart;
        }

        throw new \RuntimeException('Either user or session ID is required');
    }

    // Add this method
    public function cleanExpiredCarts(): void
    {
        Cart::where('expires_at', '<', now())
            ->whereNull('user_id')
            ->get()
            ->each(function ($cart) {
                $cart->items()->delete();
                $cart->delete();
            });
    }

    public function addItem(Cart $cart, int $product_id, int $quantity = 1): CartItem
    {
        $product = Product::with('shop')->findOrFail($product_id);

        if (!$product->is_active) {
            throw new \RuntimeException('Product is not available');
        }

        // Check stock including reserved
        $available_stock = $product->current_stock - $product->reserved_stock;

        if ($available_stock < $quantity) {
            throw new \RuntimeException("Only {$available_stock} items available");
        }

        return DB::transaction(function () use ($cart, $product_id, $quantity, $product) {
            $existing_item = CartItem::where([
                'cart_id' => $cart->id,
                'product_id' => $product_id,
                'shop_id' => $product->shop_id,
            ])->first();

            if ($existing_item) {
                $new_quantity = $existing_item->quantity + $quantity;
                return $this->updateQuantity($existing_item, $new_quantity);
            }

            return CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product_id,
                'shop_id' => $product->shop_id,
                'quantity' => $quantity,
            ]);
        });
    }

    public function updateQuantity(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $item->delete();
            return $item;
        }

        $product = $item->product;

        if (!$product->is_active) {
            throw new \RuntimeException('Product is no longer available');
        }

        // Calculate available stock including reserved
        $available_stock = $product->current_stock - $product->reserved_stock;

        if ($available_stock < $quantity) {
            throw new \RuntimeException("Only {$available_stock} items available");
        }

        $item->quantity = $quantity;
        $item->save();

        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function mergeCarts(Cart $guestCart, User $user): Cart
    {
        return DB::transaction(function () use ($guestCart, $user) {
            // Get or create user's cart
            $userCart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['currency' => 'KES', 'expires_at' => null]
            );

            // Merge items
            foreach ($guestCart->items as $guestItem) {
                $existingItem = CartItem::where([
                    'cart_id' => $userCart->id,
                    'product_id' => $guestItem->product_id,
                    'shop_id' => $guestItem->shop_id,
                ])->first();

                if ($existingItem) {
                    $existingItem->quantity += $guestItem->quantity;
                    $existingItem->save();
                } else {
                    // Transfer the item to user's cart
                    $guestItem->update(['cart_id' => $userCart->id]);
                }
            }

            // Delete the empty guest cart
            $guestCart->delete();

            return $userCart->load('items.product');
        });
    }
}
