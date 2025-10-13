<?php

use Illuminate\Support\Facades\Route;

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

// Frontend Routes
Route::get('/', function () {
    return view('home');
});

// Authentication
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Products & Shopping
Route::get('/products', function () {
    return view('products');
});

Route::get('/wishlist', function () {
    return view('wishlist');
})->middleware('auth');

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    });
});

// Catch-all route for SPA routing (keep this last)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');