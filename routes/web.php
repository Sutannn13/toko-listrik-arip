<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController; // <-- Ini controller admin lo

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

// 4. JALUR KHUSUS ADMIN LO
Route::middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});

// 5. INI YANG LO HAPUS SEBELUMNYA (Jantung Auth Laravel Breeze)
require __DIR__ . '/auth.php';
