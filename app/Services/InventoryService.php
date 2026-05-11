<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Shop;
use App\Models\InventoryMovement;
use App\Enums\InventoryMovementTypes;
use App\Exceptions\InsufficientStockException;

class InventoryService
{
    /**
     * Get current stock for a product.
     */
    public function getCurrentStock(Product $product): int
    {
        if (!$product->track_inventory) {
            return PHP_INT_MAX; // Effectively unlimited
        }
        
        return $product->current_stock;
    }
    
    /**
     * Check if product has sufficient stock.
     */
    public function hasSufficientStock(Product $product, int $quantity): bool
    {
        if (!$product->track_inventory) {
            return true;
        }
        
        return $product->current_stock >= $quantity;
    }
    
    /**
     * Add stock to a product.
     */
    public function addStock(
        Product $product,
        int $quantity,
        InventoryMovementTypes $type,
        ?int $userId = null,
        ?string $notes = null,
        ?array $metadata = null
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantity, $type, $userId, $notes, $metadata) {
            $movement = $product->addStock(
                quantity: $quantity, 
                type: $type, 
                userId: $userId, 
                notes: $notes
            );
            
            if ($metadata) {
                $movement->metadata = $metadata;
                $movement->save();
            }
            
            Log::info("Stock added to product", [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'type' => $type->label(),
                'new_stock' => $product->current_stock
            ]);
            
            return $movement;
        });
    }
    
    /**
     * Remove stock from a product.
     */
    public function removeStock(
        Product $product,
        int $quantity,
        InventoryMovementTypes $type, // Changed from string to enum
        ?int $userId = null,
        ?string $notes = null,
        ?array $metadata = null
    ): InventoryMovement {
        if (!$this->hasSufficientStock($product, $quantity)) {
            throw new InsufficientStockException(
                "Only {$product->current_stock} units available for '{$product->name}'"
            );
        }
        
        return DB::transaction(function () use ($product, $quantity, $type, $userId, $notes, $metadata) {
            $movement = $product->removeStock(
                quantity: $quantity, 
                type: $type, 
                userId: $userId, 
                notes: $notes
            );
            
            if ($metadata) {
                $movement->metadata = $metadata;
                $movement->save();
            }
            
            Log::info("Stock removed from product", [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'type' => $type->label(),
                'new_stock' => $product->current_stock
            ]);
            
            return $movement;
        });
    }
    
    /**
     * Deduct stock for an order (at payment confirmation).
     */
    public function deductForOrder(Product $product, int $quantity, int $orderId, ?int $userId = null): InventoryMovement
    {
        return $this->removeStock(
            product: $product,
            quantity: $quantity,
            type: InventoryMovementTypes::SALE, // Use enum
            userId: $userId,
            notes: "Order #{$orderId} - Payment confirmed",
            metadata: ['order_id' => $orderId]
        );
    }
    
    /**
     * Add stock for a customer return.
     */
    public function addForReturn(Product $product, int $quantity, int $orderId, ?int $userId = null): InventoryMovement
    {
        return $this->addStock(
            product: $product,
            quantity: $quantity,
            type: InventoryMovementTypes::RETURN, // Use enum
            userId: $userId,
            notes: "Return from order #{$orderId}",
            metadata: ['order_id' => $orderId]
        );
    }
    
    /**
     * Get inventory movement history for a product.
     */
    public function getMovementHistory(Product $product, int $perPage = 20)
    {
        return $product->inventoryMovements()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Get low stock products for a shop.
     */
    public function getLowStockProducts(Shop $shop)
    {
        return Product::where('shop_id', $shop->id)
            ->where('track_inventory', true)
            ->where('current_stock', '<=', DB::raw('low_stock_threshold'))
            ->where('current_stock', '>', 0)
            ->orderBy('current_stock', 'asc')
            ->get();
    }
    
    /**
     * Get out of stock products for a shop.
     */
    public function getOutOfStockProducts(Shop $shop)
    {
        return Product::where('shop_id', $shop->id)
            ->where('track_inventory', true)
            ->where('current_stock', '<=', 0)
            ->orderBy('name', 'asc')
            ->get();
    }
    
    /**
     * Bulk update stock for multiple products.
     */
    public function bulkUpdateStock(array $updates, int $userId): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];
        
        foreach ($updates as $update) {
            try {
                $product = Product::findOrFail($update['product_id']);
                
                if ($update['type'] === 'add') {
                    $this->addStock(
                        product: $product,
                        quantity: $update['quantity'],
                        type: InventoryMovementTypes::RESTOCK, // Use enum
                        userId: $userId,
                        notes: $update['notes'] ?? null
                    );
                } else {
                    $this->removeStock(
                        product: $product,
                        quantity: $update['quantity'],
                        type: InventoryMovementTypes::ADJUSTMENT, // Use enum
                        userId: $userId,
                        notes: $update['notes'] ?? null
                    );
                }
                
                $results['success'][] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'new_stock' => $product->current_stock
                ];
                
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'product_id' => $update['product_id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}