# Migrations

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('phone', 20)->nullable();
    $table->unsignedTinyInteger('role')->default(3);
    $table->unsignedTinyInteger('status')->default(1);
    $table->string('image')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->boolean('is_anonymized')->default(false);
    $table->timestamp('deleted_at')->nullable();
    $table->timestamps();
});

Schema::create('user_addresses', function (Blueprint $table) {
    $table->id();
    $table->timestamps();
});

Schema::create('shop_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->string('image')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});

Schema::create('shops', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 150);
    $table->string('slug')->unique();
    $table->string('custom_slug')->nullable()->unique();
    $table->text('description')->nullable();
    $table->string('logo_image')->nullable();
    $table->string('cover_image')->nullable();
    $table->string('contact_email')->nullable();
    $table->string('contact_phone')->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_verified')->default(false);
    $table->json('settings')->nullable(); // Store shop preferences
    $table->foreignId('shop_category_id')->nullable()->constrained('shop_categories')->nullOnDelete();
    $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->unique(['owner_id', 'name']);

    $table->index(['is_active', 'is_verified']);
});

Schema::create('product_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 100)->unique();
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('image')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('parent_id')->nullable()->constrained('product_categories')->cascadeOnDelete();
    $table->timestamps();

    $table->index('slug');
    $table->index('parent_id');
    $table->index(['is_active', 'sort_order']);
});

Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 200);
    $table->string('slug')->unique();
    $table->string('sku')->nullable()->unique();
    $table->text('description')->nullable();
    $table->decimal('cost_price', 12, 2)->nullable();
    $table->decimal('price', 12, 2);
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_active')->default(true);
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
    $table->timestamps();
});

Schema::create('product_images', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->timestamps();

    $table->index(['product_id', 'sort_order']);
});

Schema::create('discounts', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name');
    $table->decimal('value', 10, 2);
    $table->unsignedTinyInteger('type')->default(0); // 0 = percentage, 1 = amount
    $table->unsignedTinyInteger('scope')->default(0); // 0 = shop_wide, 1= product_category, 2 = specific_products
    $table->timestamp('starts_at');
    $table->timestamp('expires_at');
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();

    $table->index(['shop_id', 'is_active', 'starts_at', 'expires_at']);
});

Schema::create('discount_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
    $table->foreignId('product_category_id')->constraned('product_categories')->cascadeOnDelete();
    $table->timestamps();

    $table->index(['discount_id', 'product_category_id']);
});

Schema::create('discount_products', function (Blueprint $table) {
    $table->id();
    $table->boolean('exclude_from_category_discounts')->default(false);
    $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
    $table->foreignId('product_id')->constraned('products')->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['discount_id', 'product_id']);

    $table->index(['discount_id', 'product_id']);
    $table->index(['discount_id', 'exclude_from_category_discounts']);
});
```
