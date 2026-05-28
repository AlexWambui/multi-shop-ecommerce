<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Concerns\HasUuid;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;

class Order extends Model
{
    use HasUuid;

    protected $guarded = [];

    protected $casts = [
        'order_status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'customer_details_snapshot' => 'array',
        'delivery_details_snapshot' => 'array',
        'pricing_snapshot' => 'array',
        'payment_snapshot' => 'array',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->customer_details_snapshot['name'] ??
            $this->customer?->name ?? null;
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id');
    }

    public function markAsPaid(?string $transactionId = null): void
    {
        $this->update([
            'payment_status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);

        if ($transactionId && $this->payment) {
            $this->payment->update(['transaction_id' => $transactionId]);
        }
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('order_status', OrderStatus::PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::PAID);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('order_status', OrderStatus::PROCESSING);
    }

    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('order_status', OrderStatus::SHIPPED);
    }

    public function scopeByShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeDateRange(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeCurrentMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeLastMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->subMonth()->year);
    }

    public function getOrderStatusLabelAttribute(): string
    {
        return $this->order_status?->label();
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->payment_status?->label();
    }
}
