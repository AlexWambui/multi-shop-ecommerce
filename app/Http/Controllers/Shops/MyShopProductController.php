<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Exception;
use App\Models\Shop;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Http\Requests\Products\ProductRequest;
use App\Http\Resources\Products\ProductsPageResource;

class MyShopProductController extends Controller
{
    public function index(Request $request, Shop $shop)
    {
        $query = $shop->products();

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

        $products = $query->orderBy('name')->paginate(20);

        return inertia('app/shops/my-shops/products/Index', [
            'shop' => $shop,
            'products' => ProductsPageResource::collection($products),
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
            ],
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

        $images = $validated_data['images'] ?? [];
        unset($validated_data['images']);

        try {
            DB::beginTransaction();

            $product = $shop->products()->create($validated_data);

            if (!empty($images)) {
                $this->uploadImages($images, $product);
            }

            DB::commit();
        
            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Product created successfully"
            ]);

            return to_route('my-shops.products.index', $shop->slug);
        } catch (Exception $e) {
            DB::rollback();

            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Failed to save product: {$e->getMessage()}"
            ]);
        }
    }

    public function edit(Shop $shop, Product $product)
    {
        $product_categories = ProductCategory::select('id', 'name')->orderBy('name')->get();

        $product->load('category', 'images');

        return inertia('app/shops/my-shops/products/Edit', [
            'shop' => $shop,
            'product' => $product,
            'product_categories' => $product_categories
        ]);
    }
    
    public function update(ProductRequest $request, Shop $shop, Product $product)
    {
        $validated = $request->validated();

        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $imagesToDelete = $request->input('images_to_delete', []);
        if (is_string($imagesToDelete)) {
            $imagesToDelete = json_decode($imagesToDelete, true) ?? [];
        }

        DB::beginTransaction();

        try {

            $product->update($validated);

            // DELETE ONLY SELECTED IMAGES
            if (!empty($imagesToDelete)) {
                $oldImages = $product->images()->whereIn('id', $imagesToDelete)->get();

                foreach ($oldImages as $image) {
                    Storage::disk('public')->delete("products/{$image->name}");
                    $image->delete();
                }
            }

            // NEXT SORT ORDER (IMPORTANT FIX)
            $nextSortOrder = $product->images()->max('sort_order') ?? 0;

            // ADD NEW IMAGES
            foreach ($images as $index => $file) {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                $file->storeAs('products', $filename, 'public');

                $product->images()->create([
                    'name' => $filename,
                    'sort_order' => $nextSortOrder + $index + 1,
                ]);
            }

            DB::commit();

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Product updated successfully"
            ]);

            return to_route('my-shops.products.index', $shop->slug);

        } catch (\Throwable $e) {
            DB::rollBack();

            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Product failed to update: {$e->getMessage()}"
            ]);

            return back()->withErrors(['error' => $e->getMessage()]);
        }
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

    protected function uploadImages(array $images, Product $product): void
    {
        foreach ($images as $index => $image) {
            $extension = $image->getClientOriginalExtension();
            $slug = Str::slug($product->name);
            $timestamp = now()->format('Ymd_His');
            $random = Str::random(6);
            $filename = "{$slug}_{$product->id}_{$index}_{$timestamp}_{$random}.{$extension}";
            
            $path = $image->storeAs('products', $filename, 'public');
            
            $product->images()->create([
                'name' => $filename,
                'sort_order' => $index,
            ]);
        }
    }
}
