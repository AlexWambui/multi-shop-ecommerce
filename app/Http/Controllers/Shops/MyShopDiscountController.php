<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;
use App\Models\Shop;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Http\Resources\Discounts\DiscountResource;
use App\Http\Requests\Discounts\DiscountRequest;

class MyShopDiscountController extends Controller
{
    public function index(Shop $shop, Request $request)
    {
        $query = Discount::forShop($shop->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $discounts = $query->orderByDesc('created_at')
            ->withCount(['products', 'categories'])
            ->paginate(20);

        return inertia('app/shops/my-shops/discounts/Index', [
            'discounts' => DiscountResource::collection($discounts),
            'shop' => $shop,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create(Shop $shop)
    {
        $categories = ProductCategory::select('id', 'name')->get();
        $products = Product::where('shop_id', $shop->id)->select('id', 'name', 'price')->get();

        return inertia('app/shops/my-shops/discounts/Create', [
            'shop' => $shop,
            'categories' => $categories,
            'products' => $products
        ]);
    }

    public function edit(Shop $shop, Discount $discount)
    {
        // Ensure the discount belongs to the shop
        if ($discount->shop_id !== $shop->id) {
            abort(403, 'Unauthorized access to this discount');
        }

        // Load the relationships
        $discount->load(['categories', 'products']);
        
        $categories = ProductCategory::select('id', 'name')->get();
        $products = Product::where('shop_id', $shop->id)->select('id', 'name', 'price')->get();

        // Prepare the discount data with selected IDs
        $discountData = [
            'id' => $discount->id,
            'name' => $discount->name,
            'type' => $discount->type,
            'value' => $discount->value,
            'scope' => $discount->scope,
            'starts_at' => $discount->starts_at,
            'expires_at' => $discount->expires_at,
            'is_active' => $discount->is_active,
            'category_ids' => $discount->categories->pluck('id')->toArray(),
            'product_ids' => $discount->products->pluck('id')->toArray(),
        ];

        return inertia('app/shops/my-shops/discounts/Edit', [
            'shop' => $shop,
            'discount' => $discountData,
            'categories' => $categories,
            'products' => $products
        ]);
    }

    public function store(DiscountRequest $request, Shop $shop)
    {
        try {
            $validated_data = $request->validated();

            // Extract relationship data
            $targetData = [
                'category_ids' => $validated_data['category_ids'] ?? [],
                'product_ids' => $validated_data['product_ids'] ?? []
            ];

            // Remove relationship arrays from validated data
            unset($validated_data['category_ids'], $validated_data['product_ids']);

            DB::transaction(function () use ($validated_data, $targetData, $shop) {
                $discount = $shop->discounts()->create($validated_data);
                
                // Pass the array of target data
                $this->syncDiscountTargets($discount, $targetData);
            });

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Discount created successfully"
            ]);

            return to_route('my-shops.discounts.index', ['shop' => $shop->slug]);
            
        } catch (Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to create discount: " . $e->getMessage()
            ]);
            
            return back()->withInput();
        }
    }

    public function update(DiscountRequest $request, Shop $shop, Discount $discount)
    {
        try {
            // Ensure the discount belongs to the shop
            if ($discount->shop_id !== $shop->id) {
                abort(403, 'Unauthorized access to this discount');
            }

            $validated_data = $request->validated();

            // Extract relationship data
            $targetData = [
                'category_ids' => $validated_data['category_ids'] ?? [],
                'product_ids' => $validated_data['product_ids'] ?? []
            ];

            // Remove relationship arrays from validated data
            unset($validated_data['category_ids'], $validated_data['product_ids']);

            DB::transaction(function () use ($validated_data, $targetData, $discount) {
                // Update the discount
                $discount->update($validated_data);
                
                // Sync the relationships
                $this->syncDiscountTargets($discount, $targetData);
            });

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Discount updated successfully"
            ]);

            return to_route('my-shops.discounts.index', ['shop' => $shop->slug]);
            
        } catch (Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to update discount: " . $e->getMessage()
            ]);
            
            return back()->withInput();
        }
    }

    public function destroy(Shop $shop, Discount $discount)
    {
        try {
            // Ensure the discount belongs to the shop
            if ($discount->shop_id !== $shop->id) {
                abort(403, 'Unauthorized access to this discount');
            }

            DB::transaction(function () use ($discount) {
                // Detach all relationships first
                $discount->categories()->detach();
                $discount->products()->detach();
                
                // Delete the discount
                $discount->delete();
            });

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Discount deleted successfully"
            ]);

            return redirect()->back();
            
        } catch (Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to delete discount: " . $e->getMessage()
            ]);
            
            return redirect()->back();
        }
    }

    protected function syncDiscountTargets(Discount $discount, array $data): void
    {
        switch ($discount->scope) {
            case Discount::SCOPE_PRODUCT_CATEGORY:
                $categoryIds = $data['category_ids'] ?? [];
                // Sync categories
                $discount->categories()->sync($categoryIds);
                // Clear products if any
                $discount->products()->detach();
                break;

            case Discount::SCOPE_SPECIFIC_PRODUCTS:
                $productIds = $data['product_ids'] ?? [];
                // Sync products
                $discount->products()->sync($productIds);
                // Clear categories if any
                $discount->categories()->detach();
                break;

            case Discount::SCOPE_SHOP_WIDE:
                // Clear both relationships
                $discount->categories()->detach();
                $discount->products()->detach();
                break;
        }
    }
}