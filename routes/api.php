<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payments\MpesaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// M-Pesa Webhook (no auth - called by Safaricom)
Route::post('/webhooks/mpesa/callback', [MpesaController::class, 'callback'])->name('webhooks.mpesa.callback');
