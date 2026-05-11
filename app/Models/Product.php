<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\InventoryMovement;
use App\Enums\InventoryMovementTypes;
use App\Concerns\HasUuid;
use App\Concerns\HasSlug;

class Product extends Model
{
    use HasUuid, HasSlug;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $appends = [
        'category_name',
        'thumbnail_url'
    ];

    protected static function booted()
    {
        static::updating(function ($product) {
            if ($product->isDirty('name')) {
                $old_name = $product->getOriginal('name');
                $new_name = $product->name;
                
                // Update slug
                $product->slug = Str::slug($new_name);
                
                // Get images directly from database
                $images = $product->images()->get();
                
                $new_slug = Str::slug($new_name);
                
                // Rename all images
                foreach ($images as $image) {
                    $old_filename = $image->name;
                    
                    // Extract the current slug from the filename (first part before first underscore)
                    $parts = explode('_', $old_filename);
                    $old_slug = $parts[0];
                    
                    // Replace old slug with new slug
                    $new_filename = str_replace($old_slug, $new_slug, $old_filename);
                    
                    if ($old_filename !== $new_filename) {
                        $old_path = 'products/' . $old_filename;
                        $new_path = 'products/' . $new_filename;
                        
                        // Rename the actual file
                        if (Storage::disk('public')->exists($old_path)) {
                            Storage::disk('public')->move($old_path, $new_path);
                        }
                        
                        // Update database record
                        $image->name = $new_filename;
                        $image->save();
                    }
                }
            }
        });

        static::deleting(function ($product) {
            $images = $product->images()->get();
            
            foreach ($images as $image) {
                $path = "products/{$image->name}";
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $image->delete();
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function getCategoryNameAttribute(): string
    {
        return $this->category?->name ?? 'Uncategorized';
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->images->first()?->image_url ?? asset('assets/images/default.png');
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(
            Discount::class,
            'discount_products',
            'product_id',
            'discount_id'
        )->withTimestamps();
    }

    // Get all active discounts for this product (includes shop-wide and category)
    public function getActiveDiscountsAttribute()
    {
        return Discount::active()
            ->forShop($this->shop_id)
            ->forProduct($this->id)
            ->get();
    }

    // Get the best active discount for this product
    public function getBestDiscountAttribute(): ?Discount
    {
        $discounts = $this->active_discounts;
        
        if ($discounts->isEmpty()) {
            return null;
        }

        // Calculate which discount gives the best value
        $bestDiscount = null;
        $bestAmount = 0;

        foreach ($discounts as $discount) {
            $amount = $discount->calculateDiscount($this->price, $this->id, 1);
            
            if ($amount > $bestAmount) {
                $bestAmount = $amount;
                $bestDiscount = $discount;
            }
        }

        return $bestDiscount;
    }

    // Get discounted price (if any active discount applies)
    public function getDiscountedPriceAttribute(): float
    {
        $bestDiscount = $this->best_discount;
        
        if (!$bestDiscount) {
            return $this->price;
        }

        $discountAmount = $bestDiscount->calculateDiscount($this->price, $this->id, 1);
        return max(0, $this->price - $discountAmount);
    }

    public function getProfitMarginAttribute(): float
    {
        if (!$this->cost_price || $this->cost_price <=0) {
            return 0;
        }

        return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function hasSufficientStock(int $quantity): bool
    {
        if (!$this->track_inventory) {
            return true;
        }
        
        return $this->current_stock >= $quantity;
    }

    public function getStockStatusAttribute(): string
    {
        if (!$this->track_inventory) {
            return 'Unlimited';
        }
        
        if ($this->current_stock <= 0) {
            return 'Out of Stock';
        }
        
        if ($this->current_stock <= $this->low_stock_threshold) {
            return "Low Stock ({$this->current_stock} left)";
        }
        
        return "In Stock ({$this->current_stock} available)";
    }

    public function getStockBadgeClassAttribute(): string
    {
        if (!$this->track_inventory) {
            return 'bg-gray-100 text-gray-800';
        }
        
        if ($this->current_stock <= 0) {
            return 'bg-red-100 text-red-800';
        }
        
        if ($this->current_stock <= $this->low_stock_threshold) {
            return 'bg-orange-100 text-orange-800';
        }
        
        return 'bg-green-100 text-green-800';
    }

    public function updateStock(int $newQuantity, InventoryMovementTypes $type, ?int $userId = null, ?string $notes = null, ?array $metadata = null): InventoryMovement
    {
        $oldQuantity = $this->current_stock;
        $quantityChange = $newQuantity - $oldQuantity;
        
        // Create movement record
        $movement = InventoryMovement::create([
            'product_id' => $this->id,
            'shop_id' => $this->shop_id,
            'user_id' => $userId,
            'type' => $type,
            'quantity' => $quantityChange,
            'quantity_before' => $oldQuantity,
            'quantity_after' => $newQuantity,
            'notes' => $notes,
            'metadata' => $metadata
        ]);
        
        // Update product stock
        $this->current_stock = $newQuantity;
        $this->save();
        
        return $movement;
    }

    public function addStock(int $quantity, InventoryMovementTypes $type, ?int $userId = null, ?string $notes = null): InventoryMovement
    {
        $newQuantity = $this->current_stock + $quantity;
        return $this->updateStock($newQuantity, $type, $userId, $notes);
    }

    public function removeStock(int $quantity, InventoryMovementTypes $type, ?int $userId = null, ?string $notes = null)
    {
        if (!$this->hasSufficientStock($quantity)) {
            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Cannot remove {$quantity} units. Only {$this->current_stock} available."
            ]);

            return redirect()->back();
        }
        
        $newQuantity = $this->current_stock - $quantity;
        return $this->updateStock($newQuantity, $type, $userId, $notes);
    }
}
