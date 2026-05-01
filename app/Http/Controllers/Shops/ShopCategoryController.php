<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;
use App\Http\Requests\Shops\ShopCategoryRequest;
use App\Models\ShopCategory;

class ShopCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ShopCategory::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')->get();

        return inertia('app/shops/categories/Index', [
            'categories' => $categories
        ]);
    }

    public function create()
    {
        return inertia('app/shops/categories/Create');
    }

    public function store(ShopCategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            ShopCategory::create([
                'name' => $request->name,
            ]);

            DB::commit();

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Category: {$request->name} created successfully"
            ]);

            return to_route('shop-categories.index');
        } catch (Exception $e) {
            DB::rollBack();

            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Failed to update user: {$e->getMessage()}"
            ]);

            return back()->withInput();
        }
    }

    public function edit(ShopCategory $shop_category)
    {
        return inertia('app/shops/categories/Edit', [
            'shop_category' => $shop_category
        ]);
    }

    public function update(ShopCategory $shop_category, ShopCategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $shop_category->update([
                'name' => $request->name,
            ]);

            DB::commit();

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Category: {$request->name} updated successfully"
            ]);

            return to_route('shop-categories.index');
        } catch (Exception $e) {
            DB::rollBack();
            
            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Failed to update user: {$e->getMessage()}"
            ]);

            return back()->withInput();
        }
    }

    public function destroy(ShopCategory $shop_category)
    {
        try {
            $shop_category->delete();

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Category deleted successfully"
            ]);

            return to_route('shop-categories.index');
        } catch (Exception $e) {
            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Failed to update user: {$e->getMessage()}"
            ]);

            return back()->withInput();
        }
    }
}
