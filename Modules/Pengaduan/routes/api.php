<?php

use Illuminate\Support\Facades\Route;
use Modules\Pengaduan\Http\Controllers\PengaduanController;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('pengaduan')->group(function () {
        Route::post('/', [PengaduanController::class, 'create']);
        Route::get('/', [PengaduanController::class, 'getAllAduan']);
        Route::get('/{id}', [PengaduanController::class, 'getDetailAduan']);
        Route::put('/{id}/processed', [PengaduanController::class, 'processedStatusAduan']);
        Route::put('/{id}/confirmed', [PengaduanController::class, 'confirmedStatusPengaduan']);
        Route::put('/{id}/approved', [PengaduanController::class, 'approvedStatusPengaduan']);
    });
});
