<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Guest\HomePageController;
use App\Http\Controllers\Guest\DealsPageController;
use App\Http\Controllers\Guest\GuestShopController;
use App\Http\Controllers\Guest\GuestProductController;
use App\Http\Controllers\Guest\CartController;
use App\Http\Controllers\Guest\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Shops\ShopController;
use App\Http\Controllers\Shops\ShopCategoryController;
use App\Http\Controllers\Shops\MyShopController;
use App\Http\Controllers\Shops\MyShopProductController;
use App\Http\Controllers\Shops\MyShopDiscountController;
use App\Http\Controllers\Shops\MyShopInventoryController;
use App\Http\Controllers\Products\ProductCategoryController;
use App\Http\Controllers\DeliveryLocations\DeliveryLocationController;
use App\Http\Controllers\DeliveryLocations\DeliveryAreaController;
use App\Http\Controllers\Payments\MpesaController;
use App\Http\Controllers\Payments\StripeController;
use App\Http\Controllers\Sales\OrderController;

Route::get('/', [HomePageController::class, 'homePage'])->name('home');
Route::get('/deals-page', [DealsPageController::class, 'index'])->name('deals-page');
Route::get('all-shops', [GuestShopController::class, 'listAllShops'])->name('guest-shops.all');
Route::get('shop-details/{shop:slug}', [GuestShopController::class, 'shopDetails'])->name('guest-shops.details');
Route::get('product-details/{product:slug}', [GuestProductController::class, 'productDetails'])->name('guest-products.details');

Route::get('cart', [CartController::class, 'index'])->name('cart.index');
Route::get('cart/summary', [CartController::class, 'summary'])->name('cart.summary');
Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('cart/item/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('cart/item/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::get('order-details/{order:uuid}', [OrderController::class, 'orderDetailsPage'])->name('order-details-page');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::prefix('payment')->group(function() {
        Route::get('/mpesa', [MpesaController::class, 'index'])->name('payment.mpesa');
        Route::post('/mpesa/process', [MpesaController::class, 'process'])->name('payment.mpesa.process');
        Route::get('/mpesa/{order:uuid}/status', [MpesaController::class, 'status'])->name('payment.mpesa.status');
        Route::get('/mpesa/{order:uuid}/query-status', [MpesaController::class, 'queryStatus'])->name('payment.mpesa.query');

        Route::get('/stripe', [StripeController::class, 'index'])->name('payment.stripe');
        Route::post('/stripe/process', [StripeController::class, 'process'])->name('payment.stripe.process');
    });
    Route::get('/api/locations/{location}/areas', [DeliveryAreaController::class, 'getAreasByLocation'])->name('api.locations.areas');
});

Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {
    Route::prefix('users')
        ->name('users.')
        ->controller(UserController::class)
        ->group(function()
    {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::prefix('shop-categories')
        ->name('shop-categories.')
        ->controller(ShopCategoryController::class)
        ->group( function()
    {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{shop_category}/edit', 'edit')->name('edit');
        Route::put('/{shop_category}', 'update')->name('update');
        Route::delete('/{shop_category}', 'destroy')->name('destroy');
    });

    Route::prefix('shops')
        ->name('shops.')
        ->controller(ShopController::class)
        ->group( function()
    {
        Route::get('/', 'index')->name('index');
    });

    Route::prefix('product-categories')
        ->name('product-categories.')
        ->controller(ProductCategoryController::class)
        ->group( function()
    {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{product_category}/edit', 'edit')->name('edit');
        Route::put('/{product_category}', 'update')->name('update');
        Route::delete('/{product_category}', 'destroy')->name('destroy');
    });

    Route::prefix('delivery-locations')
        ->name('delivery-locations.')
        ->controller(DeliveryLocationController::class)
        ->group( function()
    {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{delivery_location:uuid}/show', 'show')->name('show');
        Route::get('/{delivery_location:uuid}/edit', 'edit')->name('edit');
        Route::put('/{delivery_location:uuid}', 'update')->name('update');
        Route::delete('/{delivery_location:uuid}', 'destroy')->name('destroy');
    });

    Route::prefix('delivery-areas')
        ->name('delivery-areas.')
        ->controller(DeliveryAreaController::class)
        ->group( function()
    {
        Route::get('/{delivery_location:uuid}/create', 'create')->name('create');
        Route::post('/{delivery_location:uuid}/', 'store')->name('store');
        Route::get('/{delivery_location:uuid}/{delivery_area:uuid}/edit', 'edit')->name('edit');
        Route::put('/{delivery_location:uuid}/{delivery_area:uuid}', 'update')->name('update');
        Route::delete('/{delivery_area:uuid}', 'destroy')->name('destroy');
    });
});

Route::middleware(['auth', 'verified', 'role:seller'])->group(function () {
    Route::prefix('my-shops')
        ->name('my-shops.')
        ->controller(MyShopController::class)
        ->group( function()
    {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{shop}/show', 'show')->name('show');
        Route::get('/{shop}/edit', 'edit')->name('edit');
        Route::put('/{shop}', 'update')->name('update');
        Route::delete('/{shop}', 'destroy')->name('destroy');

        Route::prefix('{shop:slug}/products')
            ->name('products.')
            ->controller(MyShopProductController::class)
            ->group( function()
        {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{product}/edit', 'edit')->name('edit');
            Route::put('/{product}', 'update')->name('update');
            Route::delete('/{product}', 'destroy')->name('destroy');
        });

        Route::prefix('{shop:slug}/discounts')
            ->name('discounts.')
            ->controller(MyShopDiscountController::class)
            ->group( function()
        {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{discount}/edit', 'edit')->name('edit');
            Route::put('/{discount}', 'update')->name('update');
            Route::delete('/{discount}', 'destroy')->name('destroy');
        });

        Route::prefix('{shop:slug}/inventory')
            ->name('inventory.')
            ->controller(MyShopInventoryController::class)
            ->group( function()
        {
            Route::get('/', 'index')->name('index');
            Route::get('{product}/create', 'create')->name('create');
            Route::post('{product}/', 'store')->name('store');
            Route::get('/{product}/edit', 'edit')->name('edit');
            Route::put('/{product}', 'update')->name('update');
            Route::get('/{product}/history', 'history')->name('history');
        });
    });
});

Route::inertia('/welcome', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('welcome');

require __DIR__.'/settings.php';
