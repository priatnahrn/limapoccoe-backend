<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::get('ping', function () {
    return response()->json(['message' => 'API ready 🔥']);
});

Route::prefix('auth')->group(function () {

    // Registration and Login Routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register/otp', [AuthController::class, 'verifyOtp']);
    Route::post('register/resend', [AuthController::class, 'resendOtp']);
    Route::post('login/masyarakat', [AuthController::class, 'loginMasyarakat']);
    Route::post('login/admin', [AuthController::class, 'loginInternal']);
 
    Route::middleware(['auth:api'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

});