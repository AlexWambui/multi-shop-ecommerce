<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
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
            return $this->returnEmptyDeals($product_categories);
        }

        $all_product_ids = $active_discounts
            ->flatMap(fn($discount) => $discount->eligible_product_ids)
            ->unique()
            ->values()
            ->toArray();
        
        $products = Product::whereIn('id', $all_product_ids)
            ->where('is_active', true)
            ->with('shop')
            ->get()
            ->keyBy('id');

        $products_with_discounts = $products->map(function ($product) use ($active_discounts) {
            foreach ($active_discounts as $discount) {
                if (in_array($product->id, $discount->eligible_product_ids)) {
                    $product->discount_pct = $discount->discount_pct;
                    break;
                }
            }
            return $product;
        });        

        $flash_sales = $products_with_discounts->filter(fn ($product) => $product->discount_pct && $product->discount_pct < 30)->take(8);

        $clearance_sales = $products_with_discounts->filter(fn ($product) => $product->discount_pct && $product->discount_pct >= 30)->take(8);

        return inertia('guest/dealspage/Index', [
            'product_categories' => $product_categories,
            'flash_sales' => ProductCardResource::collection($flash_sales),
            'clearance_sales' => ProductCardResource::collection($clearance_sales)
        ]);
    }

    public function returnEmptyDeals(Collection|array $product_categories)
    {
        return inertia('guest/dealspage/Index', [
            'product_categories' => $product_categories,
            'flash_sales' => ['data' => []],
            'clearance_sales' => ['data' => []]
        ]);
    }
}


