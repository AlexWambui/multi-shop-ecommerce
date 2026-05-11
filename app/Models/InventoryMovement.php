<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Concerns\HasUuid;
use App\Enums\InventoryMovementTypes;

class InventoryMovement extends Model
{
    use HasUuid;

    protected $guarded = [];

    protected $casts = [
        'type' => InventoryMovementTypes::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? 'Unknown';
    }
}
