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

Schema::create('delivery_locations', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('name')->unique();
    string('slug')->unique();
    boolean('is_active')->default(true);
    integer('sort_order')->default(0);
    timestamps();
});

Schema::create('delivery_areas', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('name')->unique();
    string('slug')->unique();
    decimal('shipping_cost', 10, 2);
    integer('estimated_days')->default(2);
    boolean('is_active')->default(true);
    integer('sort_order')->default(0);
    foreignId('delivery_location_id')->constrained('delivery_locations')->cascadeOnDelete();
    timestamps();

    unique(['name', 'delivery_location_id']);
    index(['delivery_location_id', 'is_active']);
});

Schema::create('orders', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('order_number')->unique();
    decimal('subtotal', 12, 2);
    decimal('discount_amount', 12, 2)->default(0);
    decimal('shipping_cost', 12, 2)->default(0);
    decimal('tax_amount', 12, 2)->default(0);
    decimal('total_amount', 12, 2);
    unsignedTinyInteger('order_status')->default(0); // ENUM: 0 = pending, 1 = processing, 3 = shipped, 4 = processed.

    unsignedTinyInteger('payment_method')->nullable(); // ENUM: 0 = mpesa, 1 = stripe
    unsignedTinyInteger('payment_status')->default(0); // ENUM: 0 = pending, 1 = paid, 2 cancelled
    timestamp('paid_at')->nullable();
    timestamp('cancelled_at')->nullable();

    text('notes')->nullable();

    // Snapshots of customer info, delivery, pricing at order time (for when user gets anonymized)
    // customer: name, email, phone
    json('customer_details_snapshot')->nullable();
    // delivery location, delivery area, delivery address, phone
    json('delivery_details_snapshot')->nullable();
    // subtotal, shipping, discount, tax, total
    json('pricing_snapshot')->nullable();
    // method, phone, transaction_id
    json('payment_snapshot')->nullable();

    foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
    foreignId('customer_id')->constrained('users')->cascadeOnDelete();
    timestamps();

    index(['customer_id', 'status']);
    index('order_number');
    index('shop_id');
    index(['order_status', 'payment_status']);
    index('created_at');
});

Schema::create('order_items', function (Blueprint $table) {
    id();
    integer('quantity');
    decimal('unit_price', 12, 2);
    decimal('discount', 12, 2)->default(0);
    decimal('total_price', 12, 2);

    // Snapshot of product info (in case product is deleted later)
    string('product_name_snapshot', 200);
    string('product_sku_snapshot', 100);
    foreignId('order_id')->constrained('orders')->cascadeOnDelete();
    foreignId('product_id')->constrained('products')->nullOnDelete();
    timestamps();

    index(['order_id']);
    index(['product_id']);
    index(['order_id', 'product_id']);
});

Schema::create('payments', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('transaction_id')->unique(); // From payment gateway
    decimal('amount', 12, 2);
    unsignedTinyInteger('payment_status')->default(0); // ENUM: 0 = pending, 1 = paid, 2 = cancelled
    unsignedTinyInteger('payment_method')->nullable(); // ENUM: 0 = mpesa, 1 = stripe
    json('gateway_response')->nullable(); // Store webhook data
    string('failure_reason')->nullable();
    foreignId('order_id')->constrained('orders')->cascadeOnDelete();
    timestamps();

    index('transaction_id');
});
```
