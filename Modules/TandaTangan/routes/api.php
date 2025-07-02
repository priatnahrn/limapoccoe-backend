<?php

use Illuminate\Support\Facades\Route;
use Modules\TandaTangan\Http\Controllers\TandaTanganController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tandatangans', TandaTanganController::class)->names('tandatangan');
});
