<?php

namespace App\Services;

use App\Models\User;

class ShopLimitService
{
    public function canCreateShop(User $user): bool
    {
        // Rule 1: Only sellers and admins can create shops
        if (!in_array($user->role, [0, 1, 2])) {
            return false;
        }
        
        // Rule 2: Admins have no limits
        if (in_array($user->role, [0, 1])) {
            return true;
        }
        
        // Rule 3: Sellers have a limit of 1 shop (for now)
        // This is easily changeable when you add subscriptions
        return $user->shops()->count() < 1;
    }
    
    public function getRemainingShops(User $user): int
    {
        if (!in_array($user->role, [0, 1, 2])) {
            return 0;
        }
        
        if (in_array($user->role, [0, 1])) {
            return PHP_INT_MAX;
        }
        
        return max(0, 1 - $user->shops()->count());
    }
    
    public function getMessage(User $user): string
    {
        return match($user->role) {
            3 => 'Customers cannot create shops. Please contact support to become a seller.',
            2 => 'You can create up to 1 shop. Upgrade your plan to create more.',
            default => 'You have reached your shop limit.',
        };
    }
}