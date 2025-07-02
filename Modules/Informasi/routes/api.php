<?php

use Illuminate\Support\Facades\Route;
use Modules\Informasi\Http\Controllers\InformasiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('informasis', InformasiController::class)->names('informasi');
});
