# Migrations

```php
Schema::create('users', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('name');
    string('email')->unique();
    string('phone', 20)->nullable();
    unsignedTinyInteger('role')->default(3);
    unsignedTinyInteger('status')->default(1);
    string('image')->nullable();
    timestamp('email_verified_at')->nullable();
    string('password');
    rememberToken();
    boolean('is_anonymized')->default(false);
    timestamp('deleted_at')->nullable();
    timestamps();
});

Schema::create('user_addresses', function (Blueprint $table) {
    id();
    timestamps();
});

Schema::create('shop_categories', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('name')->unique();
    string('slug')->unique();
    string('image')->nullable();
    integer('sort_order')->default(0);
    timestamps();
});

Schema::create('shops', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('name', 150);
    string('slug')->unique();
    string('custom_slug')->nullable()->unique();
    text('description')->nullable();
    string('logo_image')->nullable();
    string('cover_image')->nullable();
    string('contact_email')->nullable();
    string('contact_phone')->nullable();
    boolean('is_active')->default(true);
    boolean('is_verified')->default(false);
    json('settings')->nullable(); // Store shop preferences
    foreignId('shop_category_id')->nullable()->constrained('shop_categories')->nullOnDelete();
    foreignId('owner_id')->constrained('users')->cascadeOnDelete();
    softDeletes();
    timestamps();

    unique(['owner_id', 'name']);

    index(['is_active', 'is_verified']);
});

Schema::create('product_categories', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('name', 100)->unique();
    string('slug')->unique();
    text('description')->nullable();
    string('image')->nullable();
    integer('sort_order')->default(0);
    boolean('is_active')->default(true);
    foreignId('parent_id')->nullable()->constrained('product_categories')->cascadeOnDelete();
    timestamps();

    index('slug');
    index('parent_id');
    index(['is_active', 'sort_order']);
});

Schema::create('products', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('name', 200);
    string('slug')->unique();
    string('sku')->nullable()->unique();
    text('description')->nullable();
    decimal('cost_price', 12, 2)->nullable();
    decimal('price', 12, 2);
    boolean('is_featured')->default(false);
    boolean('is_active')->default(true);
    foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
    timestamps();
});

Schema::create('product_images', function (Blueprint $table) {
    id();
    string('name');
    unsignedSmallInteger('sort_order')->default(0);
    foreignId('product_id')->constrained('products')->cascadeOnDelete();
    timestamps();

    index(['product_id', 'sort_order']);
});

Schema::create('discounts', function (Blueprint $table) {
    id();
    uuid()->unique();
    string('name');
    decimal('value', 10, 2);
    unsignedTinyInteger('type')->default(0); // 0 = percentage, 1 = amount
    unsignedTinyInteger('scope')->default(0); // 0 = shop_wide, 1= product_category, 2 = specific_products
    timestamp('starts_at');
    timestamp('expires_at');
    boolean('is_active')->default(true);
    timestamps();

    foreignId('shop_id')->constrained('shops')->cascadeOnDelete();

    index(['shop_id', 'is_active', 'starts_at', 'expires_at']);
});

Schema::create('discount_categories', function (Blueprint $table) {
    id();
    foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
    foreignId('product_category_id')->constraned('product_categories')->cascadeOnDelete();
    timestamps();

    index(['discount_id', 'product_category_id']);
});

Schema::create('discount_products', function (Blueprint $table) {
    id();
    boolean('exclude_from_category_discounts')->default(false);
    foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
    foreignId('product_id')->constraned('products')->cascadeOnDelete();
    timestamps();

    unique(['discount_id', 'product_id']);

    index(['discount_id', 'product_id']);
    index(['discount_id', 'exclude_from_category_discounts']);
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

Schema::create('business_posts', function (Blueprint $table) {
    id();
    text('content');
    string('image')->nullable();
    unsignedInteger('likes_count')->default(0);
    unsignedInteger('comments_count')->default(0);

    boolean('is_pinned')->default(0);
    timestamp('pinned_at')->nullable();

    timestamp('published_at')->useCurrent();
    timestamp('scheduled_for')->nullable();
    boolean('is_draft')->default(false);

    foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    timestamps();

    index('shop_id');
    index('published_at');
    index('likes_count');
    index('comments_count');
});

Schema::create('business_post_likes', function (Blueprint $table) {
    id();
    foreignId('business_post_id')->constrained('business_posts')->cascadeOnDelete();
    foreignId('user_id')->constrained('users')->cascadeOnDelete();
    timestamps();

    unique(['business_post_id', 'user_id']);
});

Schema::create('business_post_comments', function (Blueprint $table) {
    id();
    uuid()->unique();
    text('content');
    unsignedInteger('likes_count')->default(0);

    foreignId('business_post_id')->constrained('business_posts')->cascadeOnDelete();
    foreignId('user_id')->constrained('users')->cascadeOnDelete();
    foreignId('parent_id')->nullable()->constrained('business_post_comments')->cascadeOnDelete();
    timestamps();

    index(['business_post_id', 'created_at']);
});
```
