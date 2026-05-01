<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\HasUuid;
use App\Concerns\HasSlug;

class ShopCategory extends Model
{
    use HasUuid, HasSlug;

    protected $guarded = [];
}
