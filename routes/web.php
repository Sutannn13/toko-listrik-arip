<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController; // <-- Ini controller admin lo
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\WarrantyClaimController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileAddressController;

// 1. HALAMAN DEPAN (Publik)
Route::get('/', [HomeController::class, 'index']);
Route::get('/katalog', [HomeController::class, 'index'])->name('home');
Route::get('/produk/{slug}', [HomeController::class, 'show'])->name('home.products.show');
Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms-and-conditions', 'legal.terms')->name('legal.terms');

Route::middleware('auth')->group(function () {
    Route::post('/produk/{slug}/buy', [HomeController::class, 'buy'])->name('home.products.buy');
    Route::post('/produk/{slug}/review', [HomeController::class, 'submitReview'])
        ->middleware('throttle:5,1')
        ->name('home.products.review');
    Route::get('/keranjang', [HomeController::class, 'cart'])->name('home.cart');
    Route::patch('/keranjang/{productId}', [HomeController::class, 'updateCart'])->name('home.cart.update');
    Route::delete('/keranjang/{productId}', [HomeController::class, 'removeFromCart'])->name('home.cart.remove');
    Route::get('/checkout', [HomeController::class, 'checkoutPage'])->name('home.checkout');
    Route::post('/checkout', [HomeController::class, 'checkout'])
        ->middleware('throttle:12,1')
        ->name('home.cart.checkout');

    Route::get('/cek-pesanan', [HomeController::class, 'tracking'])->name('home.tracking');
    Route::post('/cek-pesanan', [HomeController::class, 'checkTracking'])
        ->middleware('throttle:10,1')
        ->name('home.tracking.check');
    Route::get('/cek-pesanan/{orderCode}', [HomeController::class, 'showTracking'])
        ->name('home.tracking.show');
    Route::post('/cek-pesanan/{orderCode}/payment-proof', [HomeController::class, 'uploadPaymentProof'])
        ->middleware('throttle:6,1')
        ->name('home.tracking.proof');
    Route::get('/cek-pesanan/{orderCode}/payment-proof/{payment}', [HomeController::class, 'viewPaymentProof'])
        ->name('home.tracking.proof.view');
    Route::post('/cek-pesanan/{orderCode}/refund-request', [HomeController::class, 'requestRefund'])
        ->middleware('throttle:5,1')
        ->name('home.tracking.refund');
    Route::post('/cek-pesanan/{orderCode}/bayargg/regenerate', [HomeController::class, 'regenerateBayarGgPaymentLink'])
        ->middleware('throttle:10,1')
        ->name('home.tracking.bayargg.regenerate');
});

// 2. DASHBOARD LEGACY (Breeze) -> redirect ke halaman yang sesuai role
Route::get('/dashboard', function () {
    $user = request()->user();

    if ($user && $user->hasAnyRole(['super-admin', 'admin'])) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

// 3. PROFILE USER (Bawaan Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/photo/{user}', [ProfileController::class, 'photo'])->name('profile.photo');
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
    Route::get('/klaim-garansi', [HomeController::class, 'warrantyClaims'])
        ->name('home.warranty-claims.index');
    Route::get('/klaim-garansi/{warrantyClaim}/proof', [HomeController::class, 'viewWarrantyClaimProof'])
        ->name('home.warranty-claims.proof.view');

    Route::get('/garansi', [HomeController::class, 'warrantyCenter'])
        ->name('home.warranty');
    Route::get('/riwayat-transaksi', [HomeController::class, 'transactionHistory'])
        ->name('home.transactions');
    Route::get('/notifikasi', [HomeController::class, 'notifications'])
        ->name('home.notifications.index');
    Route::get('/notifikasi/{notification}/open', [HomeController::class, 'openNotification'])
        ->name('home.notifications.open');
    Route::post('/notifikasi/baca-semua', [HomeController::class, 'markAllNotificationsRead'])
        ->name('home.notifications.read-all');
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
        Route::post('/products/{product}/adjust-stock', [\App\Http\Controllers\Admin\ProductController::class, 'adjustStock'])
            ->name('products.adjust-stock');

        // Order pipeline minimal
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::patch('/orders/{order}/payments/{payment}/approve', [OrderController::class, 'approvePaymentProof'])
            ->name('orders.payments.approve');
        Route::patch('/orders/{order}/payments/{payment}/reject', [OrderController::class, 'rejectPaymentProof'])
            ->name('orders.payments.reject');
        Route::patch('/orders/{order}/items/{orderItem}/warranty', [OrderController::class, 'updateItemWarranty'])
            ->name('orders.items.update-warranty');

        // Warranty claim management minimal
        Route::get('/warranty-claims', [WarrantyClaimController::class, 'index'])->name('warranty-claims.index');
        Route::get('/warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'show'])
            ->name('warranty-claims.show');
        Route::patch('/warranty-claims/{warrantyClaim}/status', [WarrantyClaimController::class, 'updateStatus'])
            ->name('warranty-claims.update-status');

        Route::get('/notifications', [AdminNotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('/notifications/{notification}/open', [AdminNotificationController::class, 'open'])
            ->name('notifications.open');
        Route::post('/notifications/read-all', [AdminNotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');

        // ── System Settings ──
        Route::middleware('role:super-admin')->group(function () {
            Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

            // ── User Management ──
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.update-role');
            Route::post('/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('users.suspend');
            Route::post('/users/{user}/unsuspend', [UserManagementController::class, 'unsuspend'])->name('users.unsuspend');
            Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        });
    });

// 5. INI YANG LO HAPUS SEBELUMNYA (Jantung Auth Laravel Breeze)
require __DIR__ . '/auth.php';
