<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
<<<<<<< HEAD
use App\Http\Controllers\Guest\HomePageController;

Route::get('/', [HomePageController::class, 'homePage'])->name('home');

Route::inertia('/welcome', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('welcome');
=======

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');
>>>>>>> 0915937 (first commit after re-install and daisy ui failure.)

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
