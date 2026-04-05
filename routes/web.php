<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController; // <-- Ini controller admin lo
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;

// 1. HALAMAN DEPAN (Publik)
Route::get('/', function () {
    return view('welcome');
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
});

// JALUR KHUSUS ADMIN
Route::middleware(['auth', 'role:super-admin'])
    ->prefix('admin') // <-- Semua URL otomatis diawali /admin
    ->name('admin.')  // <-- Semua nama rute otomatis diawali admin.
    ->group(function () {

        // Menjadi: /admin/dashboard | route('admin.dashboard')
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Menjadi: /admin/categories | route('admin.categories.index'), dll.
        Route::resource('categories', CategoryController::class);

        // Menjadi: /admin/products | route('admin.products.index'), dll.
        Route::resource('products', ProductController::class);
    });

// 5. INI YANG LO HAPUS SEBELUMNYA (Jantung Auth Laravel Breeze)
require __DIR__ . '/auth.php';
