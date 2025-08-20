<?php

use Illuminate\Support\Facades\Route;
use Modules\DataKependudukan\Http\Controllers\DataKependudukanController;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('data-kependudukan')->group(function () {
        Route::post('/', [DataKependudukanController::class, 'create']);
        Route::get('/', [DataKependudukanController::class, 'getAllDataKependudukan']);
        Route::get('/{id}', [DataKependudukanController::class, 'getDetailDataKependudukan']);
        Route::put('/{id}', [DataKependudukanController::class, 'updateDataKependudukan']);
        Route::delete('/anggota-keluarga/{id}', [DataKependudukanController::class, 'deleteAnggotaKeluarga']);
        Route::delete('/{id}', [DataKependudukanController::class, 'destroyKeluarga']);
        Route::post('/import', [DataKependudukanController::class, 'importExcel']);
    });

});

Route::prefix('statistik')->group(function () {
    Route::get('/jumlah-penduduk', [DataKependudukanController::class, 'getJumlahPenduduk']);
    Route::get('/jumlah-keluarga', [DataKependudukanController::class, 'getJumlahKeluarga']);
    Route::get('/jumlah-jenis-kelamin', [DataKependudukanController::class, 'getJumlahPerJenisKelamin']);
});