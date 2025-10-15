<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CartApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProfileApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/login', [AuthApiController::class, 'login']);
});

// Public product routes
Route::apiResource('products', ProductController::class)->only(['index', 'show']);
Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/search', [ProductController::class, 'search']);

// Protected customer routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::get('/user', [AuthApiController::class, 'user']);
    });

    // Cart routes
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartApiController::class, 'index']);
        Route::post('/items', [CartApiController::class, 'store']);
        Route::put('/items/{itemId}', [CartApiController::class, 'update']);
        Route::delete('/items/{itemId}', [CartApiController::class, 'destroy']);
        Route::delete('/', [CartApiController::class, 'clear']);
    });

    // Order routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderApiController::class, 'index']);
        Route::post('/', [OrderApiController::class, 'store']);
        Route::get('/{id}', [OrderApiController::class, 'show']);
        Route::post('/{id}/cancel', [OrderApiController::class, 'cancel']);
    });

    // Public order tracking
    Route::get('/orders/track/{orderCode}', [OrderApiController::class, 'track']);

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileApiController::class, 'show']);
        Route::put('/', [ProfileApiController::class, 'update']);
        Route::put('/password', [ProfileApiController::class, 'changePassword']);

        // Address routes
        Route::get('/addresses', [ProfileApiController::class, 'getAddresses']);
        Route::post('/addresses', [ProfileApiController::class, 'createAddress']);
        Route::get('/addresses/{id}', [ProfileApiController::class, 'getAddress']);
        Route::put('/addresses/{id}', [ProfileApiController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [ProfileApiController::class, 'deleteAddress']);
        Route::post('/addresses/{id}/default', [ProfileApiController::class, 'setDefaultAddress']);

        // Order history
        Route::get('/orders', [ProfileApiController::class, 'getOrderHistory']);
    });
});
