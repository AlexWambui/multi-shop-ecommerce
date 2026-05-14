<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $cart = Cart::where('session_id', $session_id)->first();

            // Check if cart exists and is expired
            if ($cart && $cart->expires_at && $cart->expires_at->isPast()) {
                $cart->items()->delete();
                $cart->delete();
                $cart = null;
            }

            if (!$cart) {
                $cart = Cart::create([
                    'session_id' => $session_id,
                    'currency' => 'KES',
                    'expires_at' => now()->addDays(7)
                ]);
            }

            return $cart;
        }

        throw new \RuntimeException('Either user or session ID is required');
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

    public function mergeCarts(Cart $guest_cart, User $user): Cart
    {
        return DB::transaction(function () use ($guest_cart, $user) {
            $user_cart = $this->getCart($user);

            foreach ($guest_cart->items as $guestItem) {
                try {
                    $existingItem = CartItem::where([
                        'cart_id' => $user_cart->id,
                        'product_id' => $guestItem->product_id,
                        'shop_id' => $guestItem->shop_id,
                    ])->first();

                    if ($existingItem) {
                        $total_quantity = $existingItem->quantity + $guestItem->quantity;
                        $this->updateQuantity($existingItem, $total_quantity);
                    } else {
                        $this->addItem(
                            $user_cart,
                            $guestItem->product_id,
                            $guestItem->quantity,
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to merge cart item: ' . $e->getMessage(), [
                        'item_id' => $guestItem->id,
                        'product_id' => $guestItem->product_id
                    ]);
                }
            }

            $guest_cart->delete();
            return $user_cart->load('items.product', 'items.shop');
        });
    }
}