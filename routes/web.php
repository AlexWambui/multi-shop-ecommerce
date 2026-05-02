<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Guest\HomePageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Shops\ShopCategoryController;
use App\Http\Controllers\Shops\ShopController;

Route::get('/', [HomePageController::class, 'homePage'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('shop-categories', [ShopCategoryController::class, 'index'])->name('shop-categories.index');
    Route::get('shop-categories/create', [ShopCategoryController::class, 'create'])->name('shop-categories.create');
    Route::post('shop-categories', [ShopCategoryController::class, 'store'])->name('shop-categories.store');
    Route::get('shop-categories/{shop_category}/edit', [ShopCategoryController::class, 'edit'])->name('shop-categories.edit');
    Route::put('shop-categories/{shop_category}', [ShopCategoryController::class, 'update'])->name('shop-categories.update');
    Route::delete('shop-categories/{shop_category}', [ShopCategoryController::class, 'destroy'])->name('shop-categories.destroy');
});

Route::middleware(['auth', 'verified', 'role:seller'])->group(function () {
    Route::get('shops', [ShopController::class, 'index'])->name('shops.index');
    Route::get('shops/create', [ShopController::class, 'create'])->name('shops.create');
    Route::post('shops', [ShopController::class, 'store'])->name('shops.store');
    Route::get('shops/{shop}/edit', [ShopController::class, 'edit'])->name('shops.edit');
    Route::put('shops/{shop}', [ShopController::class, 'update'])->name('shops.update');
    Route::delete('shops/{shop}', [ShopController::class, 'destroy'])->name('shops.destroy');
});

Route::inertia('/welcome', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('welcome');

require __DIR__.'/settings.php';
