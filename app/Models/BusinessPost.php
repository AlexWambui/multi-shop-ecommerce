<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPost extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_draft' => 'boolean',
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'pinned_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }
}
