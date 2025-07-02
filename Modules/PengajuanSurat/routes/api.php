<?php

use Illuminate\Support\Facades\Route;
use Modules\PengajuanSurat\Http\Controllers\PengajuanSuratController;
use Modules\PengajuanSurat\Http\Controllers\SuratController;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('surat')->group(function () {
        Route::get('/', [SuratController::class, 'getAllSurat']);
        Route::get('/{slug}', [SuratController::class, 'getDetailSurat']);
    });
});
