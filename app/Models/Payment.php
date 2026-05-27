<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Concerns\HasUuid;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;

class Payment extends Model
{
    use HasUuid;

    protected $guarded = [];

    protected $casts = [
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'gateway_response' => 'array',
        'amount' => 'decimal:2'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Payment.php
    public function isSuccessful(): bool
    {
        return $this->payment_status === PaymentStatus::PAID;
    }

    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'payment_status' => PaymentStatus::CANCELLED,
            'failure_reason' => $reason,
        ]);

        // Also update the order's payment status
        $this->order?->update(['payment_status' => PaymentStatus::CANCELLED]);
    }
}
