<?php

namespace App\Enums;



enum PaymentMethod: int
{
    case MPESA = 0;
    case STRIPE = 1;

    public function label(): string
    {
        return match($this) {
            self::MPESA => 'MPesa',
            self::STRIPE => 'Stripe'
        };
    }
}
