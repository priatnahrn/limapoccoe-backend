<?php

use Illuminate\Support\Facades\Route;
use Modules\DataKependudukan\Http\Controllers\DataKependudukanController;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('data-kependudukan')->group(function () {
        Route::post('/', [DataKependudukanController::class, 'create']);
    });
});
