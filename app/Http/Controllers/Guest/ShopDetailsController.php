<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Product;

class ShopDetailsController extends Controller
{
    public function shopDetails(string $slug)
    {
        $shop = Shop::with('category', 'owner')->where('custom_slug', $slug)->orWhere('slug', $slug)->firstOrFail();

        $products = $shop->products()->where('is_active', true)->latest()->paginate(16);

        $stats = [
            'total_products' => $shop->products()->latest()->where('is_active', true)->count(),
            // TODO: Show actual stats
            'total_sales' => 0,
            'total_reviews' => 0,
            'average_rating' => 0,
        ];

        return inertia('guest/shops/ShopDetails', [
            'shop' => $shop,
            'stats' => $stats,
            'products' => $products->items(),
            'products_pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'links' => $products->linkCollection()
            ],
        ]);
    }
}