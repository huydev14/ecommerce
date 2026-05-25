<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\OAuthController;

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/test', function () {
        return response()->json(['message' => 'Gọi API thành công']);
    });

    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/check-email', [AuthController::class, 'checkEmail']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
        Route::post('/resend-otp', [AuthController::class, 'resendOTP']);
    });

    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('auth')->group(function () {
        Route::get('google/redirect', [OAuthController::class, 'googleRedirect'])->name('oauth.google.redirect');
        Route::get('google/callback', [OAuthController::class, 'googleCallback'])->name('oauth.google.callback');
    });

    // ----- Categories API -----------------------
    Route::get('/categories/tree', [CategoryController::class, 'tree']);

    // ----- Products API -------------------------
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/new-arrivals', [ProductController::class, 'newArrivals']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    // ----- Cart API -------------------------
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::put('/cart/items/{variantId}', [CartController::class, 'update']);
    Route::delete('/cart/items/{variantId}', [CartController::class, 'destroy']);
});
