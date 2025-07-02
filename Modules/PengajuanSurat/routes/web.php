<?php

use Illuminate\Support\Facades\Route;
use Modules\PengajuanSurat\Http\Controllers\PengajuanSuratController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pengajuansurats', PengajuanSuratController::class)->names('pengajuansurat');
});
