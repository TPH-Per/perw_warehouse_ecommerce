<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\PaymentController;

Route::prefix('admin')->group(function () {
    // TODO: Add admin authentication when AdminAuthController is implemented
    // Route::post('/login', [AdminAuthController::class, 'login']);
    
    // Admin routes that require authentication and admin middleware
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Dashboard & Analytics
        Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/analytics/sales-by-category', [AnalyticsController::class, 'salesByCategory']);
        Route::get('/analytics/customer-ltv/{userId}', [AnalyticsController::class, 'customerLifetimeValue']);
        
        // User management
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
        // Product management
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::get('/products/statistics', [ProductController::class, 'statistics']);
        
        // Category management
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        
        // Inventory management
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::get('/inventory/{id}', [InventoryController::class, 'show']);
        Route::post('/inventory/{id}/adjust', [InventoryController::class, 'adjust']);
        Route::get('/inventory/{id}/history', [InventoryController::class, 'history']);
        Route::get('/inventory/statistics', [InventoryController::class, 'statistics']);
        
        // Order management
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::get('/orders/statistics', [OrderController::class, 'statistics']);
        
        // Shipment management
        Route::get('/shipments', [ShipmentController::class, 'index']);
        Route::post('/shipments', [ShipmentController::class, 'store']);
        Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
        Route::put('/shipments/{id}', [ShipmentController::class, 'update']);
        
        // Review management
        Route::get('/reviews', [ProductReviewController::class, 'index']);
        Route::put('/reviews/{id}/approve', [ProductReviewController::class, 'approve']);
        Route::put('/reviews/{id}/reject', [ProductReviewController::class, 'reject']);
        Route::delete('/reviews/{id}', [ProductReviewController::class, 'destroy']);
        
        // Supplier management
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
        Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
        
        // Warehouse management
        Route::get('/warehouses', [WarehouseController::class, 'index']);
        Route::post('/warehouses', [WarehouseController::class, 'store']);
        Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);
        Route::put('/warehouses/{id}', [WarehouseController::class, 'update']);
        Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy']);
        
        // Payment management
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
    });
});