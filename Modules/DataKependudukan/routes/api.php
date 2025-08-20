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
    Route::get('/jumlah-per-jenis-kelamin', [DataKependudukanController::class, 'getJumlahPendudukPerJenisKelamin']);
    Route::get('/jumlah-per-agama', [DataKependudukanController::class, 'getJumlahPendudukPerAgama']);
    Route::get('/jumlah-per-pendidikan', [DataKependudukanController::class, 'getJumlahPendudukPerPendidikan']);
    Route::get('/jumlah-per-pekerjaan', [DataKependudukanController::class, 'getJumlahPendudukPerPekerjaan']);
    Route::get('/jumlah-per-status-perkawinan', [DataKependudukanController::class, 'getJumlahPendudukPerPerkawinan']);
    Route::get('/jumlah-per-dusun', [DataKependudukanController::class, 'getJumlahPendudukPerDusun']);
});