<?php

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminPaymentController;
use App\Http\Controllers\Api\Admin\AdminPaymentProofController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminTimeSlotController;
use App\Http\Controllers\Api\CheckoutQuoteController;
use App\Http\Controllers\Api\DeliveryZoneController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentProofController;
use App\Http\Controllers\Api\PreorderDateController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TimeSlotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    Route::get('/categories', [ProductCategoryController::class, 'index']);
    Route::get('/preorder-dates', [PreorderDateController::class, 'index']);
    Route::get('/time-slots', [TimeSlotController::class, 'index']);
    Route::get('/delivery-zones', [DeliveryZoneController::class, 'index']);
    Route::get('/delivery-zones/lookup', [DeliveryZoneController::class, 'lookup']);

    Route::middleware('throttle:checkout')->group(function () {
        Route::post('/checkout/quote', [CheckoutQuoteController::class, 'store']);
        Route::post('/orders', [OrderController::class, 'store']);
    });

    Route::middleware('throttle:order-status')->group(function () {
        Route::get('/orders/{reference}/status', [OrderController::class, 'status'])->name('orders.status');
        Route::post('/orders/{reference}/payment-proof', [PaymentProofController::class, 'store'])->name('orders.payment-proof');
    });

    Route::prefix('admin')->group(function () {
        Route::post('/login', [AdminAuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AdminAuthController::class, 'logout']);

            // Shared: both owner and staff — staff's job is payment review only.
            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
            Route::patch('/payments/{payment}/review', [AdminPaymentController::class, 'review']);
            Route::get('/payment-proofs/{proof}', [AdminPaymentProofController::class, 'show'])->name('admin.payment-proofs.show');

            // Owner-only: everything else.
            Route::middleware('admin.role:owner')->group(function () {
                Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);

                Route::get('/categories', [AdminCategoryController::class, 'index']);
                Route::post('/categories', [AdminCategoryController::class, 'store']);
                Route::patch('/categories/{category}', [AdminCategoryController::class, 'update']);
                Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

                Route::get('/products', [AdminProductController::class, 'index']);
                Route::post('/products', [AdminProductController::class, 'store']);
                Route::patch('/products/{product}', [AdminProductController::class, 'update']);
                Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);

                Route::get('/preorder-dates/{preorderDate}/time-slots', [AdminTimeSlotController::class, 'index']);
                Route::post('/preorder-dates/{preorderDate}/time-slots', [AdminTimeSlotController::class, 'store']);
                Route::patch('/preorder-dates/{preorderDate}/time-slots/{timeSlot}', [AdminTimeSlotController::class, 'update']);
                Route::delete('/preorder-dates/{preorderDate}/time-slots/{timeSlot}', [AdminTimeSlotController::class, 'destroy']);
            });
        });
    });
});
