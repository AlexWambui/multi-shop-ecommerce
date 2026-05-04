<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Shop;

class HomePageController extends Controller
{
    public function homePage()
    {
        $featured_shops = Shop::with('category')->where('is_active', true)->orderBy('name')->limit(4)->get();

        return inertia('guest/homepage/Index', [
            'featured_shops' => $featured_shops
        ]);
    }
}