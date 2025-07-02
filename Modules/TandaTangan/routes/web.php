<?php

use Illuminate\Support\Facades\Route;
use Modules\TandaTangan\Http\Controllers\TandaTanganController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tandatangans', TandaTanganController::class)->names('tandatangan');
});
