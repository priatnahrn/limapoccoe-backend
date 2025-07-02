<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {

    Route::get('ping', function () {
        return response()->json(['message' => 'API ready 🔥']);
    });

    // Register tanpa otentikasi
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register/otp', [AuthController::class, 'verifyOtp']);

    Route::post('register/resend', [AuthController::class, 'resendOtp']);

    Route::post('login', [AuthController::class, 'login']);
 
    Route::middleware(['auth:api'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

});