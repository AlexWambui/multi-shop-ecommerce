<?php

namespace App\Enums;

enum PaymentStatus: int
{
    case PENDING = 0;
    case PAID = 1;
    case CANCELLED = 2;
    case FAILED = 3;
    case REFUNDED = 4;

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }
}
