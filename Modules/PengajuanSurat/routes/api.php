<?php

use Illuminate\Support\Facades\Route;
use Modules\PengajuanSurat\Http\Controllers\PengajuanSuratController;
use Modules\PengajuanSurat\Http\Controllers\SuratController;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('surat')->group(function () {
        Route::get('/', [SuratController::class, 'getAllSurat']);
        Route::get('/{slug}', [SuratController::class, 'getDetailSurat']);

        // Rute pengajuan yang berada di dalam surat/{slug}
        Route::prefix('{slug}/pengajuan')->group(function () {
            Route::post('/', [PengajuanSuratController::class, 'ajukanSurat']);
            Route::get('/', [PengajuanSuratController::class, 'getPengajuanSurat']);
            Route::get('/{id}', [PengajuanSuratController::class, 'getDetailPengajuanSurat']);
            Route::put('/{id}/number', [PengajuanSuratController::class, 'fillNumber']);
            Route::put('/{id}/rejected', [PengajuanSuratController::class, 'rejectedStatusPengajuan']);
            Route::put('/{id}/confirmed', [PengajuanSuratController::class, 'confirmedStatusPengajuan']);
            Route::put('/{id}/signed', [PengajuanSuratController::class, 'signedStatusPengajuan']);  
            Route::get('/{id}/download', [PengajuanSuratController::class, 'downloadSurat']);


        });
        
        // Rute yang tidak tergantung slug
        Route::put('/pengajuan/{id}', [PengajuanSuratController::class, 'updatePengajuan']);
        Route::delete('/pengajuan/{id}', [PengajuanSuratController::class, 'deletePengajuan']);
    });
    Route::get('test-pdf', [PengajuanSuratController::class, 'testDownloadPdf']);
    Route::get('test-pdf-blade', [PengajuanSuratController::class, 'testPdfBlade']);
});
