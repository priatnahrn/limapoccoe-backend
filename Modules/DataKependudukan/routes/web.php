<?php

use Illuminate\Support\Facades\Route;
use Modules\DataKependudukan\Http\Controllers\DataKependudukanController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('datakependudukans', DataKependudukanController::class)->names('datakependudukan');
});
