<?php

use Illuminate\Support\Facades\Route;
use Modules\Profile\Http\Controllers\ProfileController;


Route::middleware(['auth:api'])->group(function () {
   Route::prefix('profile')->group(function () {
        Route::post('/lengkapi-profil', [ProfileController::class, 'lengkapiProfilMasyarakat']);
        Route::get('/masyarakat', [ProfileController::class, 'getProfileDataMasyarakat']);
   });
});