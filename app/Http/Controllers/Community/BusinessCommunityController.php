<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;

class BusinessCommunityController extends Controller
{
    public function index()
    {
        $shops = Shop::get();

        return inertia('app/community/business/Index', [
            'shops' => $shops
        ]);
    }
}
