<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessPostComment extends Model
{
    protected $guarded = [];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function businessPost():BelongsTo
    {
        return $this->belongsTo(BusinessPost::class);
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentComment():BelongsTo
    {
        return $this->belongsTo(BusinessPostComment::class, 'parent_id');
    }

    public function childComments():HasMany
    {
        return $this->hasMany(BusinessPostComment::class, 'parent_id');
    }
}
