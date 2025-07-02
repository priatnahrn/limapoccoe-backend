<?php

use Illuminate\Support\Facades\Route;
use Modules\Pengaduan\Http\Controllers\PengaduanController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pengaduans', PengaduanController::class)->names('pengaduan');
});
