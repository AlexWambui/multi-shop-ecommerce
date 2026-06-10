<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessPostLike extends Model
{
    protected $guarded = [];

    public function businessPost()
    {
        return $this->belongsTo(BusinessPost::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
