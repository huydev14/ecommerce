<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\OAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/test', function () {
        return response()->json(['message' => 'Gọi API thành công']);
    });

    Route::post('/check-email', [AuthController::class, 'checkEmail']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('/resend-otp', [AuthController::class, 'resendOTP']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api_customer')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('auth')->group(function () {
        Route::get('{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
        Route::get('{provider}/callback', [OAuthController::class, 'callback']);
    });
});
