<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\User;
use App\Http\Requests\Shops\ShopRequest;
use App\Services\ShopLimitService;

class MyShopController extends Controller
{
    public function user(): User
    {
        return Auth::user();
    }

    public function __construct(protected shopLimitService $shopLimitService){}

    public function index()
    {
        $shops = $this->user()->shops()->with('category')->orderBy('name')->get();

        return inertia('app/shops/my-shops/Index', [
            'shops' => $shops
        ]);
    }

    public function create()
    {
        $shop_categories = ShopCategory::orderBy('name')->get();

        return inertia('app/shops/my-shops/Create', [
            'shop_categories' => $shop_categories
        ]);
    }

    public function store(ShopRequest $request)
    {
        $validated = $request->validated();
        $custom_slug = $request->custom_slug ? Str::slug($request->custom_slug) : null;

        if (!$this->shopLimitService->canCreateShop($this->user())) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => "{$this->shopLimitService->getMessage($this->user())}"
            ]);

            return to_route('my-shops.index');
        }

        try {
            DB::beginTransaction();

            $shop = $this->user()->shops()->create([
                'name' => $validated['name'],
                'custom_slug' => $custom_slug,
                'description' => $validated['description'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'shop_category_id' => $validated['shop_category_id'] ?? null
            ]);

            if ($request->hasFile('logo_image')) {
                $logo_path = $this->uploadImage($request->file('logo_image'), 'logo', $shop);
                $shop->update(['logo_image' => $logo_path]);
            }

            if ($request->hasFile('cover_image')) {
                $cover_path = $this->uploadImage($request->file('cover_image'), 'cover', $shop);
                $shop->update(['cover_image' => $cover_path]);
            }

            DB::commit();

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Shop created successfully"
            ]);

            return to_route('my-shops.index');
        } catch (Exception $e) {
            DB::rollback();

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to update user: {$e->getMessage()}"
            ]);

            return back()->withInput();
        }
    }

    public function show(Shop $shop)
    {
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }

        $shop->load('category');

        $total_products = $shop->products()->count();

        return inertia('app/shops/my-shops/Show', [
            'shop' => $shop,
            'stats' => [
                'total_products' => $total_products
            ],
        ]);
    }

    public function edit(Shop $shop)
    {
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }

        $shop->load('category');

        $shop_categories = ShopCategory::select('id', 'name')->get();

        return inertia('app/shops/my-shops/Edit', [
            'shop' => $shop,
            'shop_categories' => $shop_categories
        ]);
    }

    public function update(ShopRequest $request, Shop $shop)
    {
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }

        $validated = $request->validated();
        $custom_slug = $request->custom_slug ? Str::slug($request->custom_slug) : null;

        try {
            DB::beginTransaction();

            $shop->update([
                'name' => $validated['name'],
                'custom_slug' => $custom_slug,
                'description' => $validated['description'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'shop_category_id' => $validated['shop_category_id'] ?? null
            ]);

            // Handle logo upload (model handles deletion of old image via booted method)
            if ($request->hasFile('logo_image')) {
                // Delete old logo if exists
                if ($shop->logo_image) {
                    $oldPath = "shops/logos/{$shop->logo_image}";
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                
                $logo_path = $this->uploadImage($request->file('logo_image'), 'logo', $shop);
                $shop->update(['logo_image' => $logo_path]);
            }

            // Handle cover upload (delete old if new uploaded)
            if ($request->hasFile('cover_image')) {
                if ($shop->cover_image) {
                    $oldPath = "shops/covers/{$shop->cover_image}";
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                
                $cover_path = $this->uploadImage($request->file('cover_image'), 'cover', $shop);
                $shop->update(['cover_image' => $cover_path]);
            }

            DB::commit();

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Shop updated successfully"
            ]);

            return to_route('my-shops.index');
        } catch (Exception $e) {
            DB::rollback();

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to update shop: {$e->getMessage()}"
            ]);

            return back()->withInput();
        }
    }

    public function destroy(Shop $shop)
    {
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }

        $shop->deleteImages();

        $shop->delete();

        Inertia::flash('toast', [
            'type' => "success",
            'message' => "Shop deleted successfully"
        ]);

        return redirect()->back();
    }

    private function uploadImage($file, string $type, Shop $shop): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('dmY_His');
        $slug = Str::slug($shop->name, '_');

        $filename = "{$slug}_{$type}_{$shop->id}_{$timestamp}.{$extension}";

        // Determine directory
        $directory = $type === 'logo' ? 'shops/logos' : 'shops/covers';
        
        // Store the file and get the path
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Return the file name
        return $filename;
    }
}
