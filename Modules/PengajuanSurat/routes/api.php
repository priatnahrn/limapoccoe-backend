<?php

use Illuminate\Support\Facades\Route;
use Modules\PengajuanSurat\Http\Controllers\PengajuanSuratController;
use Modules\PengajuanSurat\Http\Controllers\SuratController;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('surat')->group(function () {
        Route::get('/', [SuratController::class, 'getAllSurat']);
        Route::get('/{slug}', [SuratController::class, 'getDetailSurat']);
    });

    Route::prefix('pengajuan-surat')->group(function () {
        Route::post('/{suratId}', [PengajuanSuratController::class, 'ajukanSurat']);
        Route::get('/{suratId}', [PengajuanSuratController::class, 'getPengajuanSurat']);
        Route::get('/{id}', [PengajuanSuratController::class, 'getDetailPengajuan']);
        Route::put('/{id}', [PengajuanSuratController::class, 'updatePengajuan']);
        Route::delete('/{id}', [PengajuanSuratController::class, 'deletePengajuan']);
    });
});
