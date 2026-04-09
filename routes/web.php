<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController; // <-- Ini controller admin lo
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\WarrantyClaimController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileAddressController;

// 1. HALAMAN DEPAN (Publik)
Route::get('/', [HomeController::class, 'landing'])->name('landing');
Route::get('/katalog', [HomeController::class, 'index'])->name('home');
Route::get('/produk/{slug}', [HomeController::class, 'show'])->name('home.products.show');

Route::middleware('auth')->group(function () {
    Route::post('/produk/{slug}/buy', [HomeController::class, 'buy'])->name('home.products.buy');
    Route::get('/keranjang', [HomeController::class, 'cart'])->name('home.cart');
    Route::patch('/keranjang/{productId}', [HomeController::class, 'updateCart'])->name('home.cart.update');
    Route::delete('/keranjang/{productId}', [HomeController::class, 'removeFromCart'])->name('home.cart.remove');
    Route::post('/keranjang/checkout', [HomeController::class, 'checkout'])
        ->middleware('throttle:3,10')
        ->name('home.cart.checkout');

    Route::get('/cek-pesanan', [HomeController::class, 'tracking'])->name('home.tracking');
    Route::post('/cek-pesanan', [HomeController::class, 'checkTracking'])
        ->middleware('throttle:10,1')
        ->name('home.tracking.check');
    Route::post('/cek-pesanan/{orderCode}/payment-proof', [HomeController::class, 'uploadPaymentProof'])
        ->middleware('throttle:6,1')
        ->name('home.tracking.proof');
});

// 2. DASHBOARD USER BIASA (Bawaan Breeze)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 3. PROFILE USER (Bawaan Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/profile/addresses', [ProfileAddressController::class, 'index'])->name('profile.addresses.index');
    Route::post('/profile/addresses', [ProfileAddressController::class, 'store'])->name('profile.addresses.store');
    Route::patch('/profile/addresses/{address}', [ProfileAddressController::class, 'update'])->name('profile.addresses.update');
    Route::delete('/profile/addresses/{address}', [ProfileAddressController::class, 'destroy'])->name('profile.addresses.destroy');
    Route::patch('/profile/addresses/{address}/default', [ProfileAddressController::class, 'setDefault'])
        ->name('profile.addresses.default');

    Route::post('/orders/{order}/items/{orderItem}/warranty-claim', [HomeController::class, 'submitWarrantyClaim'])
        ->name('home.warranty-claims.store');
});

// JALUR KHUSUS ADMIN
Route::middleware(['auth', 'admin.access', 'role:super-admin|admin'])
    ->prefix('admin') // <-- Semua URL otomatis diawali /admin
    ->name('admin.')  // <-- Semua nama rute otomatis diawali admin.
    ->group(function () {

        // Menjadi: /admin/dashboard | route('admin.dashboard')
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Menjadi: /admin/categories | route('admin.categories.index'), dll.
        Route::resource('categories', CategoryController::class);

        // Menjadi: /admin/products | route('admin.products.index'), dll.
        Route::resource('products', ProductController::class);

        // Order pipeline minimal
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');

        // Warranty claim management minimal
        Route::get('/warranty-claims', [WarrantyClaimController::class, 'index'])->name('warranty-claims.index');
        Route::get('/warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'show'])
            ->name('warranty-claims.show');
        Route::patch('/warranty-claims/{warrantyClaim}/status', [WarrantyClaimController::class, 'updateStatus'])
            ->name('warranty-claims.update-status');
    });

// 5. INI YANG LO HAPUS SEBELUMNYA (Jantung Auth Laravel Breeze)
require __DIR__ . '/auth.php';
