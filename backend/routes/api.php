<?php

use App\Http\Controllers\Api\CheckoutQuoteController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PreorderDateController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/preorder-dates', [PreorderDateController::class, 'index']);

    Route::middleware('throttle:checkout')->group(function () {
        Route::post('/checkout/quote', [CheckoutQuoteController::class, 'store']);
        Route::post('/orders', [OrderController::class, 'store']);
    });

    Route::middleware('throttle:order-status')->group(function () {
        Route::get('/orders/{reference}/status', [OrderController::class, 'status'])->name('orders.status');
    });
});
