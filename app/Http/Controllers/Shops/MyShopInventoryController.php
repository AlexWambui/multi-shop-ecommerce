<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;
use App\Models\Shop;
use App\Models\Product;
use App\Enums\InventoryMovementTypes;
use App\Http\Requests\Products\InventoryMovementRequest;
use App\Http\Resources\Products\InventoryMovementResource;
use App\Http\Resources\Products\ProductsPageResource;
use App\Services\InventoryService;

class MyShopInventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService){}

    public function index(Shop $shop, Request $request)
    {
        $query = Product::where('shop_id', $shop->id);
        
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->where('current_stock', '>', 0)
                          ->where('track_inventory', true);
                    break;
                case 'low_stock':
                    $query->where('track_inventory', true)
                          ->where('current_stock', '>', 0)
                          ->whereColumn('current_stock', '<=', 'low_stock_threshold');
                    break;
                case 'out_of_stock':
                    $query->where('track_inventory', true)
                          ->where('current_stock', '<=', 0);
                    break;
                case 'unlimited':
                    $query->where('track_inventory', false);
                    break;
            }
        }
        
        $products = $query->orderBy('name')->paginate(20);
        
        // Get summary stats
        $stats = [
            'total_products' => Product::where('shop_id', $shop->id)->count(),
            'low_stock_count' => $this->inventoryService->getLowStockProducts($shop)->count(),
            'out_of_stock_count' => $this->inventoryService->getOutOfStockProducts($shop)->count(),
            'total_value' => Product::where('shop_id', $shop->id)
                ->where('track_inventory', true)
                ->sum(DB::raw('current_stock * cost_price'))
        ];

        return inertia('app/shops/my-shops/inventory/Index', [
            'shop' => $shop,
            'products' => ProductsPageResource::collection($products),
            'stats' => $stats,
            'filters' => $request->only(['search', 'stock_status'])
        ]);
    }

    public function create(Shop $shop, Product $product)
    {
        return inertia('app/shops/my-shops/inventory/Create', [
            'shop' => $shop,
            'product' => $product,
            'current_stock' => $product->current_stock,
            'low_stock_threshold' => $product->low_stock_threshold,
            'track_inventory' => $product->track_inventory,
            'movement_types' => [
                'add' => InventoryMovementTypes::addOperations(),
            ]
        ]);
    }

    public function store(InventoryMovementRequest $request, Shop $shop, Product $product)
    {
        $validated = $request->validated();

        try {
            // Convert the integer type from form to enum
            $movement_type = InventoryMovementTypes::from($validated['type']);
                    
            $movement = $this->inventoryService->addStock(
                product: $product,
                quantity: $request->quantity,
                type: $movement_type,
                userId: $shop->owner->id,
                notes: $request->notes
            );

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Inventory updated successfully"
            ]);

            return to_route('my-shops.inventory.index', $shop->slug);
        } catch (Exception $e) {
            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Failed to update inventory: ' . {$e->getMessage()}"
            ]);

            return back();
        }
    }

    public function edit(Shop $shop, Product $product)
    {
        return inertia('app/shops/my-shops/inventory/Edit', [
            'shop' => $shop,
            'product' => $product,
            'current_stock' => $product->current_stock,
            'low_stock_threshold' => $product->low_stock_threshold,
            'track_inventory' => $product->track_inventory,
            'movement_types' => [
                'remove' => InventoryMovementTypes::removeOperations(),
            ]
        ]);
    }

    public function update(InventoryMovementRequest $request, Shop $shop, Product $product)
    {
        $validated = $request->validated();

        try {
            // Convert the integer type from form to enum
            $movement_type = InventoryMovementTypes::from($validated['type']);
            
            // Check if there's enough stock
            if (!$this->inventoryService->hasSufficientStock($product, $validated['quantity'])) {
                Inertia::flash('toast', [
                    'type' => "error",
                    'message' => "Insufficient stock. Only {$product->current_stock} units available."
                ]);
                
                return back();
            }
            
            $movement = $this->inventoryService->removeStock(
                product: $product,
                quantity: $validated['quantity'],
                type: $movement_type,
                userId: $shop->owner->id,
                notes: $validated['notes'] ?? null
            );

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Stock removed successfully"
            ]);

            return to_route('my-shops.inventory.index', $shop->slug);
        } catch (Exception $e) {
            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Failed to remove stock: " . $e->getMessage()
            ]);

            return back();
        }
    }

    public function history(Shop $shop, Product $product)
    {
        $movements = $this->inventoryService->getMovementHistory($product, 50);
        
        return Inertia::render('app/shops/my-shops/inventory/History', [
            'shop' => $shop,
            'product' => $product,
            'movements' => InventoryMovementResource::collection($movements)
        ]);
    }
}
