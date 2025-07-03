<?php

use Illuminate\Support\Facades\Route;
use Modules\Pengaduan\Http\Controllers\PengaduanController;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('pengaduan')->group(function () {
        Route::post('/', [PengaduanController::class, 'create']);
        Route::get('/', [PengaduanController::class, 'getAllAduan']);
        Route::get('/{id}', [PengaduanController::class, 'getDetailAduan']);
        Route::put('/{id}/confirmed', [PengaduanController::class, 'confirmedPengaduan']);
        Route::put('/{id}/approved', [PengaduanController::class, 'approvedPengaduan']);
    });
});
