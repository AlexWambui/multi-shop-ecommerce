<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Discount;
use App\Http\Resources\Products\ProductCardResource;

class DealsPageController extends Controller
{
    public function index()
    {
        $product_categories = ProductCategory::orderBy('name')->get();

        $active_discounts = Discount::active()->with('shop')->get();

        if ($active_discounts->isEmpty()) {
            return inertia('guest/dealspage/Deals', [
                'deals' => [],
                'total' => 0,
                'product_categories' => $product_categories,
                'flash_sales' => [],
                'clearance_sales' => []
            ]);
        }

        $all_discounted_products = collect();

        foreach ($active_discounts as $discount) {
            $product_ids = $discount->eligible_product_ids;

            $products = Product::whereIn('id', $product_ids)
                ->where('is_active', true)
                ->with('shop')
                ->get()
                ->map(function ($product) use ($discount) {
                    return $product;
                });

            $all_discounted_products = $all_discounted_products->merge($products);
        }

        $all_discounted_products = $all_discounted_products->unique('id');

        $flash_sales = $all_discounted_products->filter(function ($product) {
            return $product->discount_pct && $product->discount_pct < 30;
        })->take(8);

        $clearance_sales = $all_discounted_products->filter(function ($product) {
            return $product->discount_pct && $product->discount_pct >= 30;
        })->take(8);

        return inertia('guest/dealspage/Index', [
            'product_categories' => $product_categories,
            'flash_sales' => ProductCardResource::collection($flash_sales),
            'clearance_sales' => ProductCardResource::collection($clearance_sales)
        ]);
    }
}


