<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Product;
use App\Http\Resources\Products\ProductCardResource;

class HomePageController extends Controller
{
    public function homePage()
    {
        $featured_shops = Shop::with('category')->where('is_active', true)->orderBy('name')->limit(4)->get();

        $stats = [
            'total_active_shops' => Shop::where('is_active', true)->count(),
            'total_active_products' => Product::where('is_active', true)->count()
        ];

        $hot_deals = Product::where('is_active', true)
            ->whereHas('discounts', function ($query) {
                $query->active();
            })
            ->with(['shop', 'category', 'discounts' => function ($query) {
                $query->active();
            }])
            ->limit(3)
            ->get();

        return inertia('guest/homepage/Index', [
            'featured_shops' => $featured_shops,
            'stats' => $stats,
            'hot_deals' => ProductCardResource::collection($hot_deals)
        ]);
    }
}