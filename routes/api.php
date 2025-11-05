<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CartApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ProvinceController;

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
    Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/register', [AuthApiController::class, 'register']);
});

Route::get('/provinces', [ProvinceController::class, 'index']);
Route::get('/provinces/list', [ProvinceController::class, 'list']);

// Public product and category routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/search', [ProductController::class, 'search']);

Route::get('/categories', [CategoryApiController::class, 'index']);
Route::get('/categories/{id}/products', [CategoryApiController::class, 'products']);

// Public order tracking
Route::get('/orders/track/{orderCode}', [OrderApiController::class, 'track']);

// Authenticated API routes only
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::get('/user', [AuthApiController::class, 'user']);
    });

    // Order management routes (for admin/manager)
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderApiController::class, 'index']);
        Route::get('/{id}', [OrderApiController::class, 'show']);
        Route::post('/', [OrderApiController::class, 'store']);
        Route::post('/{id}/cancel', [OrderApiController::class, 'cancel']);
    });

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartApiController::class, 'index']);
        Route::post('/items', [CartApiController::class, 'store']);
        Route::put('/items/{item}', [CartApiController::class, 'update']);
        Route::delete('/items/{item}', [CartApiController::class, 'destroy']);
        Route::delete('/', [CartApiController::class, 'clear']);
    });

    // Province routes (for admin/manager)
    Route::get('/provinces/warehouse/{province}', [ProvinceController::class, 'getWarehouse']);
});
