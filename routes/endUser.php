<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController; // Auth cho End User
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AddressController; // Quản lý địa chỉ cho End User

// Auth cho End User
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Các route yêu cầu End User đã đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    // Quản lý tài khoản người dùng (thông tin cá nhân, địa chỉ)
    Route::get('/user', function (Request $request) {
        return $request->user()->load('role', 'addresses'); // Load thêm quan hệ
    });
    Route::apiResource('addresses', AddressController::class); // CRUD địa chỉ

    // Giỏ hàng
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/update/{variantId}', [CartController::class, 'update']);
    Route::delete('/cart/remove/{variantId}', [CartController::class, 'remove']);
    Route::post('/cart/clear', [CartController::class, 'clear']);

    // Đặt mua sản phẩm
    Route::post('/orders', [OrderController::class, 'store']); // Tạo đơn hàng từ giỏ hàng
    Route::get('/orders', [OrderController::class, 'index']); // Lịch sử đơn hàng của user
    Route::get('/orders/{id}', [OrderController::class, 'show']); // Chi tiết đơn hàng

    // Thanh toán đơn hàng (chỉ khi đơn hàng ở trạng thái pending_payment)
    Route::post('/orders/{id}/pay', [OrderController::class, 'pay']);

    // Bình luận (Review) sản phẩm
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
});

// Xem sản phẩm (không cần đăng nhập)
Route::get('/products', [ProductController::class, 'index']); // Danh sách sản phẩm (có thể có filter, search)
Route::get('/products/{slug}', [ProductController::class, 'show']); // Chi tiết sản phẩm (bao gồm biến thể, hình ảnh, reviews)
Route::get('/categories', [ProductController::class, 'categories']); // Danh sách danh mục

// Quản lý kho (chỉ cho Admin, đã định nghĩa trong routes/admin.php)
// Quản lý vận chuyển (chỉ cho Admin, đã định nghĩa trong routes/admin.php)
// Quản lý bình luận, đánh giá (Admin xem/duyệt, End User tạo, đã định nghĩa ở 2 nơi)