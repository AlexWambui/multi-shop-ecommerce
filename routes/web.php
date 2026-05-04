<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Guest\HomePageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Shops\ShopCategoryController;
use App\Http\Controllers\Shops\MyShopController;
use App\Http\Controllers\Shops\MyShopProductController;
use App\Http\Controllers\Products\ProductCategoryController;

Route::get('/', [HomePageController::class, 'homePage'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
    });
});

Route::inertia('/welcome', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('welcome');

require __DIR__.'/settings.php';
