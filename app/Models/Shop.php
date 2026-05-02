<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Concerns\HasUuid;
use App\Concerns\HasSlug;

class Shop extends Model
{
    use HasUuid, HasSlug;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'settings' => 'array',
    ];

    protected $appends = [
        'logo_url',
        'cover_url',
        'category_name',
    ];

    protected static function booted()
    {
        static::updating(function ($shop) {
            if ($shop->isDirty('name')) {
                if ($shop->getOriginal('logo_image')) {
                    $old_logo = $shop->getOriginal('logo_image');
                    $new_logo = static::renameImageFile($old_logo, $shop->getOriginal('name'), $shop->name, 'logo', $shop->id);

                    if ($new_logo) {
                        $shop->logo_image = $new_logo;
                    }
                }

                if ($shop->getOriginal('cover_image')) {
                    $old_cover = $shop->getOriginal('cover_image');
                    $new_cover = static::renameImageFile($old_cover, $shop->getOriginal('name'), $shop->name, 'cover', $shop->id);

                    if ($new_cover) {
                        $shop->cover_image = $new_cover;
                    }
                }
            }
        });

        static::deleting(function ($shop) {
            $shop->deleteImages();
        });
    }

    public static function renameImageFile(string $old_filename, string $old_name, string $new_name, string $type, int $shop_id): ?string
    {
        $old_path = $type === 'logo' ? "shops/logos/{$old_filename}" : "shops/covers/{$old_filename}";
        
        if (!Storage::disk('public')->exists($old_path)) {
            return null;
        }
        
        $extension = pathinfo($old_filename, PATHINFO_EXTENSION);
        $new_slug = Str::slug($new_name);
        $old_slug = Str::slug($old_name);
        
        // Replace old slug with new slug in filename
        $new_filename = str_replace($old_slug, $new_slug, $old_filename);
        
        // If no change, add timestamp to avoid cache
        if ($new_filename === $old_filename) {
            $timestamp = now()->format('dmY_His');
            $new_filename = "{$new_slug}_{$type}_{$shop_id}_{$timestamp}.{$extension}";
        }
        
        $new_path = $type === 'logo' ? "shops/logos/{$new_filename}" : "shops/covers/{$new_filename}";
        
        // Rename the file
        if (Storage::disk('public')->move($old_path, $new_path)) {
            return $new_filename;
        }
        
        return null;
    }
    
    public function deleteImages(): void
    {
        if ($this->logo_image) {
            $path = "shops/logos/{$this->logo_image}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        
        if ($this->cover_image) {
            $path = "shops/covers/{$this->cover_image}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public static function isValidCustomSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) && strlen($slug) <= 50;
    }

    public function getPublicSlugAttribute(): string
    {
        return $this->custom_slug ?? $this->slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getLogoUrlAttribute(): string
    {
        if (!$this->logo_image) {
            return asset('assets/images/default.png');
        }

        return asset("storage/shops/logos/{$this->logo_image}");
    }

    public function getCoverUrlAttribute(): string
    {
        if (!$this->cover_image) {
            return asset('assets/images/default.png');
        }

        return asset("storage/shops/covers/{$this->cover_image}");
    }

    public function getCategoryNameAttribute(): string
    {
        return $this->category?->name ?? "Uncategorized";
    }
}
