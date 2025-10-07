<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;

Route::prefix('admin')->group(function () {
    // Auth cho Admin
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/register', [AdminAuthController::class, 'register']); // Có thể không cần cho Admin

    // Các route yêu cầu quyền Admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Quản lý người dùng
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']); // Soft delete

        // Quản lý sản phẩm
        Route::apiResource('products', AdminProductController::class); // CRUD sản phẩm, biến thể, hình ảnh
        Route::apiResource('categories', CategoryController::class); // CRUD danh mục

        // Quản lý kho
        Route::get('/warehouses', [InventoryController::class, 'listWarehouses']);
        Route::get('/inventories', [InventoryController::class, 'listInventories']); // Xem tồn kho
        Route::post('/inventories/adjust', [InventoryController::class, 'adjustInventory']); // Điều chỉnh kho
        Route::post('/inventories/inbound', [InventoryController::class, 'inboundInventory']); // Nhập kho
        Route::post('/inventories/outbound', [InventoryController::class, 'outboundInventory']); // Xuất kho (cho mục đích quản lý nội bộ)

        // Quản lý đơn hàng
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']); // Cập nhật trạng thái đơn hàng

        // Quản lý vận chuyển
        Route::get('/shipments', [ShipmentController::class, 'index']);
        Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
        Route::put('/shipments/{id}/status', [ShipmentController::class, 'updateStatus']); // Cập nhật trạng thái vận chuyển

        // Quản lý bình luận, đánh giá
        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::put('/reviews/{id}/approve', [AdminReviewController::class, 'approve']);
        Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']); // Soft delete
    });
});