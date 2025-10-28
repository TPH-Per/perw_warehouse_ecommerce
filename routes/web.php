<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\InventoryAdminController;
use App\Http\Controllers\Payment\VnpayController;
use App\Http\Controllers\Payment\TestQrController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EndUser\HomeController as EndUserHomeController;
use App\Http\Controllers\EndUser\ProductController as EndUserProductController;
use App\Http\Controllers\EndUser\AuthController as EndUserAuthController;
use App\Http\Controllers\EndUser\CartController as EndUserCartController;
use App\Http\Middleware\EnsureEndUserAuthenticated;
use App\Http\Controllers\EndUser\CheckoutController as EndUserCheckoutController;
use App\Http\Controllers\EndUser\OrderController as EndUserOrderController;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Routes
Route::get('/', [EndUserHomeController::class, 'index'])->name('enduser.home');

// End-user product routes
Route::get('/product/{id}', [EndUserProductController::class, 'show'])->name('enduser.product');

// End-user auth routes (web guard)
Route::get('/enduser/login', [EndUserAuthController::class, 'showLogin'])->name('enduser.login');
Route::post('/enduser/login', [EndUserAuthController::class, 'login']);
Route::get('/enduser/register', [EndUserAuthController::class, 'showRegister'])->name('enduser.register');
Route::post('/enduser/register', [EndUserAuthController::class, 'register']);
Route::post('/enduser/logout', [EndUserAuthController::class, 'logout'])->name('enduser.logout');

// End-user cart (protected)
Route::middleware([EnsureEndUserAuthenticated::class])->group(function () {
    Route::get('/cart', [EndUserCartController::class, 'index'])->name('enduser.cart');
    Route::post('/cart/add', [EndUserCartController::class, 'add'])->name('enduser.cart.add');
    Route::post('/cart/{detailId}/update', [EndUserCartController::class, 'update'])->name('enduser.cart.update');
    Route::post('/cart/{detailId}/remove', [EndUserCartController::class, 'remove'])->name('enduser.cart.remove');

    // Checkout
    Route::get('/checkout', [EndUserCheckoutController::class, 'show'])->name('enduser.checkout');
    Route::post('/checkout', [EndUserCheckoutController::class, 'place'])->name('enduser.checkout.place');

    // Orders
    Route::get('/orders', [EndUserOrderController::class, 'index'])->name('enduser.orders');
    Route::get('/orders/{id}', [EndUserOrderController::class, 'show'])->name('enduser.order.show');
    Route::get('/orders/{id}/confirmation', [EndUserOrderController::class, 'confirmation'])->name('enduser.order.confirmation');
    Route::post('/orders/{id}/cancel', [EndUserOrderController::class, 'cancel'])->name('enduser.order.cancel');
});

// VNPAY Payment Routes
Route::prefix('payment/vnpay')->name('payment.vnpay.')->group(function () {
    // Create payment and redirect (protected)
    Route::get('/create/{order}', [VnpayController::class, 'create'])->middleware('auth')->name('create');

    // Return URL (public)
    Route::get('/return', [VnpayController::class, 'return'])->name('return');

    // IPN (public, GET/POST depending on VNPAY config)
    Route::match(['get', 'post'], '/ipn', [VnpayController::class, 'ipn'])->name('ipn');
});

// Checkout.vn Payment Routes (Removed)

// Local-only Test QR payment routes
if (app()->environment('local')) {
    Route::prefix('payment/test-qr')->name('payment.testqr.')->group(function () {
        Route::get('/{order}', [TestQrController::class, 'show'])->middleware('auth')->name('show');
        Route::post('/simulate/{order}', [TestQrController::class, 'simulate'])->middleware('auth')->name('simulate');
    });
}

// Test pagination route
Route::get('/test-pagination', function () {
    return view('test-pagination');
})->middleware('auth');

// Test user filter route
Route::get('/test-user-filter', function () {
    $request = request();
    Log::info('User filter request parameters:', $request->all());

    $query = \App\Models\User::with(['role', 'orders']);

    // Search functionality
    if ($request->has('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%");
        });
    }

    // Filter by role
    if ($request->has('role_id')) {
        Log::info('Filtering by role ID:', ['role_id' => $request->role_id]);
        $query->where('role_id', $request->role_id);
    }

    // Filter by status
    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    $users = $query->paginate(20);
    $roles = \App\Models\Role::all();

    Log::info('Users found:', ['count' => $users->total()]);

    return view('admin.users.index', compact('users', 'roles'));
})->middleware('auth', 'admin')->name('test.user.filter');

// Inventory Manager Routes (Protected by authentication and manager role)
Route::middleware(['auth', 'manager'])->prefix('manager')->name('manager.')->group(function () {
    // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Manager\ManagerDashboardController::class, 'index'])->name('dashboard');

    // Inventory Management (Read/Update only, NO create/delete products)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryAdminController::class, 'index'])->name('index');
        Route::get('/variants/search', [InventoryAdminController::class, 'searchVariants'])->name('variants.search');
        Route::post('/inbound', [InventoryAdminController::class, 'inbound'])->name('inbound');
        Route::get('/low-stock', [InventoryAdminController::class, 'lowStock'])->name('low-stock');
        Route::get('/transactions', [InventoryAdminController::class, 'transactions'])->name('transactions');
        Route::get('/{inventory}', [InventoryAdminController::class, 'show'])->name('show');
        Route::get('/{inventory}/edit', [InventoryAdminController::class, 'edit'])->name('edit');
        Route::post('/{inventory}/adjust', [InventoryAdminController::class, 'adjust'])->name('adjust');
        Route::post('/transfer', [InventoryAdminController::class, 'transfer'])->name('transfer');
        Route::post('/', [InventoryAdminController::class, 'store'])->name('store');
        Route::put('/{inventory}', [InventoryAdminController::class, 'update'])->name('update');
        Route::delete('/{inventory}', [InventoryAdminController::class, 'destroy'])->name('destroy');
    });

    // Direct Sales (No shipping)
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [App\Http\Controllers\Manager\DirectSalesController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Manager\DirectSalesController::class, 'create'])->name('create');
        Route::get('/warehouse-products', [App\Http\Controllers\Manager\DirectSalesController::class, 'getWarehouseProducts'])->name('warehouse-products');
        Route::post('/', [App\Http\Controllers\Manager\DirectSalesController::class, 'store'])->name('store');
        Route::get('/{order}', [App\Http\Controllers\Manager\DirectSalesController::class, 'show'])->name('show');
    });

    // Order Management (Shipping orders - view and update status)
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [App\Http\Controllers\Manager\ManagerOrderController::class, 'index'])->name('index');
        Route::get('/{order}', [App\Http\Controllers\Manager\ManagerOrderController::class, 'show'])->name('show');
        Route::put('/{order}/status', [App\Http\Controllers\Manager\ManagerOrderController::class, 'updateStatus'])->name('status.update');
        Route::put('/{order}/tracking', [App\Http\Controllers\Manager\ManagerOrderController::class, 'updateTracking'])->name('tracking.update');
    });

    // View Products Only (NO create/edit/delete)
    Route::get('/products', [ProductAdminController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductAdminController::class, 'show'])->name('products.show');
});

// Admin Routes (Protected by authentication and admin role)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics'])->name('dashboard.statistics');

    // Product Management
    Route::resource('products', ProductAdminController::class);
    Route::post('/products/categories', [ProductAdminController::class, 'storeCategory'])->name('products.categories.store');
    Route::post('/products/suppliers', [ProductAdminController::class, 'storeSupplier'])->name('products.suppliers.store');
    Route::post('/products/{product}/variants', [ProductAdminController::class, 'addVariant'])->name('products.variants.store');
    Route::post('/products/{product}/images', [ProductAdminController::class, 'uploadImages'])->name('products.images.store');
    Route::delete('/product-images/{image}', [ProductAdminController::class, 'deleteImage'])->name('products.images.destroy');
    Route::post('/products/bulk/status', [ProductAdminController::class, 'bulkUpdateStatus'])->name('products.bulk.status');

    // User Management
    Route::resource('users', UserAdminController::class);
    Route::put('/users/{user}/password', [UserAdminController::class, 'updatePassword'])->name('users.password.update');
    Route::put('/users/{user}/suspend', [UserAdminController::class, 'suspend'])->name('users.suspend');
    Route::put('/users/{user}/activate', [UserAdminController::class, 'activate'])->name('users.activate');
    Route::get('/users/statistics', [UserAdminController::class, 'statistics'])->name('users.statistics');
    Route::get('/users/export', [UserAdminController::class, 'export'])->name('users.export');

    // Order Management
    Route::resource('orders', OrderAdminController::class);
    Route::put('/orders/{order}/status', [OrderAdminController::class, 'updateStatus'])->name('orders.status.update');
    Route::post('/orders/{order}/payment', [OrderAdminController::class, 'processPayment'])->name('orders.payment.process');
    Route::post('/orders/{order}/payment/cod', [App\Http\Controllers\Payment\CashOnDeliveryController::class, 'process'])->name('orders.payment.cod');
    Route::post('/orders/{order}/shipment', [OrderAdminController::class, 'createShipment'])->name('orders.shipment.create');
    Route::post('/orders/{order}/cancel', [OrderAdminController::class, 'cancelOrder'])->name('orders.cancel');
    Route::get('/orders/statistics', [OrderAdminController::class, 'statistics'])->name('orders.statistics');
    Route::get('/orders/export', [OrderAdminController::class, 'export'])->name('orders.export');

    // Inventory Management
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryAdminController::class, 'index'])->name('index');

        // Reports & Analytics (must come before {inventory} route)
        Route::get('/low-stock', [InventoryAdminController::class, 'lowStock'])->name('low-stock');
        Route::get('/statistics', [InventoryAdminController::class, 'statistics'])->name('statistics');
        Route::get('/transactions', [InventoryAdminController::class, 'transactions'])->name('transactions');
        Route::get('/export', [InventoryAdminController::class, 'export'])->name('export');
        // Warehouses
        Route::post('/warehouses', [InventoryAdminController::class, 'storeWarehouse'])->name('warehouses.store');

        Route::get('/variants/search', [InventoryAdminController::class, 'searchVariants'])->name('variants.search');
        Route::post('/inbound', [InventoryAdminController::class, 'inbound'])->name('inbound');

        Route::post('/', [InventoryAdminController::class, 'store'])->name('store');
        Route::get('/{inventory}', [InventoryAdminController::class, 'show'])->name('show');

        // Inventory Operations
        Route::post('/{inventory}/adjust', [InventoryAdminController::class, 'adjust'])->name('adjust');
        Route::post('/transfer', [InventoryAdminController::class, 'transfer'])->name('transfer');
        Route::put('/{inventory}/reorder-level', [InventoryAdminController::class, 'setReorderLevel'])->name('reorder-level');
    });
});
