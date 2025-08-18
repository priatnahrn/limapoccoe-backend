<?php

use Illuminate\Support\Facades\Route;
use Modules\Informasi\Http\Controllers\InformasiController;

Route::prefix('informasi')->group(function () {

    Route::middleware('auth:api')->group(function () {
        Route::get('/admin', [InformasiController::class, 'getAllInformasiAdmin']);
        Route::get('/admin/{id}', [InformasiController::class, 'getDetailInformasiAdmin']);
        Route::post('/admin', [InformasiController::class, 'tambahInformasi']);
        Route::put('/admin/{id}', [InformasiController::class, 'updateInformasi']);
        Route::delete('/admin/{id}', [InformasiController::class, 'deleteInformasi']);
    });
    // ✅ Rute publik (tidak perlu login)
    Route::get('/', [InformasiController::class, 'getAllInformasiPublik']);
    Route::get('/{slug}', [InformasiController::class, 'getDetailInformasi']);

    // ✅ Rute admin (butuh login & role 'staff-desa')

});
