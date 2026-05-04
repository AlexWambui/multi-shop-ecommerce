<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Shop::select('id', 'name', 'slug', 'contact_email', 'contact_phone', 'shop_category_id', 'is_active', 'is_verified')->with('category:id,name');

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

        $shops = $query->paginate(15);

        return inertia('app/shops/shops/Index', [
            'shops' => $shops->items(),
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
            ],
            'pagination' => [
                'current_page' => $shops->currentPage(),
                'last_page' => $shops->lastPage(),
                'per_page' => $shops->perPage(),
                'total' => $shops->total(),
                'links' => $shops->linkCollection()
            ]
        ]);
    }
}
