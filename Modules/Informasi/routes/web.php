<?php

use Illuminate\Support\Facades\Route;
use Modules\Informasi\Http\Controllers\InformasiController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('informasis', InformasiController::class)->names('informasi');
});
