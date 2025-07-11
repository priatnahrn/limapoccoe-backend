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
        Route::delete('/{id}', [DataKependudukanController::class, 'deleteDataKeluarga']);
        Route::post('/import', [DataKependudukanController::class, 'importExcel']);

    });
});
