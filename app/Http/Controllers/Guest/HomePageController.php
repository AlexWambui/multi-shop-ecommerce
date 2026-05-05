<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Product;

class HomePageController extends Controller
{
    public function homePage()
    {
        $featured_shops = Shop::with('category')->where('is_active', true)->orderBy('name')->limit(4)->get();

        $total_active_shops = Shop::where('is_active', true)->count();
        $total_active_products = Product::where('is_active', true)->count();

        return inertia('guest/homepage/Index', [
            'featured_shops' => $featured_shops,
            'stats' => [
                'total_active_shops' => $total_active_shops,
                'total_active_products' => $total_active_products
            ]
        ]);
    }
}