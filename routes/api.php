<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\BayarGgWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/bayar-gg', [BayarGgWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('api.webhooks.bayar-gg');

Route::post('/ai/chat', [AiAssistantController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('api.ai.chat');

Route::post('/ai/feedback', [AiAssistantController::class, 'feedback'])
    ->middleware('throttle:40,1')
    ->name('api.ai.feedback');

Route::post('/auth/token', [AuthTokenController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.auth.token.store');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::delete('/auth/token', [AuthTokenController::class, 'destroy'])
        ->name('api.auth.token.destroy');
    Route::get('/me', [AuthTokenController::class, 'me'])
        ->name('api.auth.me');

    Route::get('/cart', [CartController::class, 'index'])->name('api.cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api.cart.items.store');
    Route::patch('/cart/items/{productId}', [CartController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('api.cart.items.update');
    Route::delete('/cart/items/{productId}', [CartController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('api.cart.items.destroy');

    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('api.checkout.store');
});
