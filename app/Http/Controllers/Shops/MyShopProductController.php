<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Shop;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Http\Requests\Products\ProductRequest;

class MyShopProductController extends Controller
{
    public function index(Request $request, Shop $shop)
    {
        $query = $shop->products()->select('id', 'name', 'slug', 'sku', 'price', 'is_active', 'product_category_id')->with('category:id,name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $products = $query->paginate(15);

        return inertia('app/shops/my-shops/products/Index', [
            'shop' => $shop,
            'products' => $products->items(),
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
            ],
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'links' => $products->linkCollection()
            ]
        ]);
    }

    public function create(Shop $shop)
    {
        $product_categories = ProductCategory::select('id', 'name')->orderBy('name')->get();

        return inertia('app/shops/my-shops/products/Create', [
            'shop' => $shop,
            'product_categories' => $product_categories
        ]);
    }

    public function store(ProductRequest $request, Shop $shop)
    {
        $validated_data = $request->validated();

        $product = $shop->products()->create($validated_data);
        
        Inertia::flash('toast', [
            'type' => "success",
            'message' => "{$product->name} created successfully"
        ]);

        return to_route('my-shops.products.index', $shop->slug);
    }

    public function edit(Shop $shop, Product $product)
    {
        $product_categories = ProductCategory::select('id', 'name')->orderBy('name')->get();

        return inertia('app/shops/my-shops/products/Edit', [
            'shop' => $shop,
            'product' => $product,
            'product_categories' => $product_categories
        ]);
    }
    
    public function update(ProductRequest $request, Shop $shop, Product $product)
    {
        $validated = $request->validated();
        
        $product->update($validated);

        Inertia::flash('toast', [
            'type' => "success",
            'message' => "Product updated successfully"
        ]);

        return to_route('my-shops.products.index', $shop->slug);
    }
    
    public function destroy(Shop $shop, Product $product)
    {
        $product->delete();

        Inertia::flash('toast', [
            'type' => "success",
            'message' => "Product deleted successfully"
        ]);
        
        return to_route('my-shops.products.index', $shop->slug);
    }
}
