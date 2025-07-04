<?php

use Illuminate\Support\Facades\Route;
use Modules\PengajuanSurat\Http\Controllers\PengajuanSuratController;

Route::get('/preview-surat/{slug}/{ajuan_id}', [PengajuanSuratController::class, 'previewSurat']);