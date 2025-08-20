<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Informasi\Http\Controllers\InformasiController;

Route::prefix('informasi')->group(function () {

    // ✅ Semua yang butuh login duluan
    Route::middleware('auth:api')->group(function () {
        Route::get('/admin', [InformasiController::class, 'getAllInformasiAdmin']);
        Route::get('/admin/{id}', [InformasiController::class, 'getDetailInformasiAdmin']);
        Route::post('/admin', [InformasiController::class, 'tambahInformasi']);
        Route::post('/admin/{id}', [InformasiController::class, 'updateInformasi']);
        Route::delete('/admin/{id}', [InformasiController::class, 'deleteInformasi']);
    });
    
    // ✅ Rute publik DITARUH PALING BAWAH
    Route::get('/', [InformasiController::class, 'getAllInformasiPublik']);
    Route::get('/{slug}', [InformasiController::class, 'getDetailInformasi']); // ← ini HARUS paling akhir
});

Route::patch('/tes-patch/{id}', [InformasiController::class, 'updateInformasi']);

Route::post('/debug-request', function (Request $req) {
    return response()->json([
        'all' => $req->all(),
        'judul' => $req->input('judul'),
        'hasFile' => $req->hasFile('gambar'),
    ]);
});
