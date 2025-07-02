<?php

use Illuminate\Support\Facades\Route;
use Modules\DataKependudukan\Http\Controllers\DataKependudukanController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('datakependudukans', DataKependudukanController::class)->names('datakependudukan');
});
