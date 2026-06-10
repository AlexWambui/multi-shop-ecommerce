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

    public function comments()
    {
        return $this->hasMany(BusinessPostComment::class)->latest();
    }
    
    public function likes()
    {
        return $this->belongsToMany(User::class, 'business_post_likes', 'business_post_id', 'user_id')->withTimestamps();
    }

    public function toggleLike(User $user)
    {
        if ($this->likes()->where('user_id', $user->id)->exists()) {
            $this->likes()->detach($user);
            $this->decrement('likes_count');
            return false;
        }
        
        $this->likes()->attach($user);
        $this->increment('likes_count');
        return true;
    }

    public function isLikedBy(User $user)
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }
}
