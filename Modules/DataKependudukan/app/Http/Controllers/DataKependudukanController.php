<?php

namespace Modules\DataKependudukan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\DataKependudukan\Http\Requests\DataKeluargaRequest;
use Modules\DataKependudukan\Models\Rumah;
use Tymon\JWTAuth\Facades\JWTAuth;
use Modules\DataKependudukan\Models\Keluarga;
use Modules\DataKependudukan\Models\Penduduk;
use Illuminate\Support\Str;

class DataKependudukanController extends Controller
{
    
   public function create(DataKeluargaRequest $request)
    {
        $admin = JWTAuth::parseToken()->authenticate();

        if (!$admin) {
            return response()->json(['error' => 'User belum login.'], 401);
        }

        if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
            return response()->json(['error' => 'Tidak memiliki akses.'], 403);
        }

        DB::beginTransaction();

        try {
            // Simpan rumah baru dari input manual
            $rumah = Rumah::create([
                'id' => Str::uuid(),
                'no_rumah' => $request->no_rumah,
                'rt_rw' => $request->rt_rw,
                'dusun' => $request->dusun,
            ]);

            // Simpan keluarga
            $keluarga = Keluarga::create([
                'id' => Str::uuid(),
                'nomor_kk' => $request->nomor_kk,
                'rumah_id' => $rumah->id,
            ]);

            // Simpan anggota jika ada
            foreach ($request->input('anggota', []) as $index => $anggota) {
                Penduduk::create([
                    'keluarga_id' => $keluarga->id,
                    'nik' => $anggota['nik'],
                    'no_urut' => $anggota['no_urut'] ?? str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                    'nama_lengkap' => $anggota['nama_lengkap'],
                    'hubungan' => $anggota['hubungan'] ?? null,
                    'tempat_lahir' => $anggota['tempat_lahir'] ?? null,
                    'tgl_lahir' => $anggota['tgl_lahir'] ?? null,
                    'jenis_kelamin' => $anggota['jenis_kelamin'] ?? null,
                    'status_perkawinan' => $anggota['status_perkawinan'] ?? null,
                    'agama' => $anggota['agama'] ?? null,
                    'pendidikan' => $anggota['pendidikan'] ?? null,
                    'pekerjaan' => $anggota['pekerjaan'] ?? null,
                    'no_bpjs' => $anggota['no_bpjs'] ?? null,
                    'nama_ayah' => $anggota['nama_ayah'] ?? null,
                    'nama_ibu' => $anggota['nama_ibu'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Data keluarga dan rumah berhasil disimpan.',
                'data' => [
                    'keluarga' => $keluarga->load('penduduks'),
                    'rumah' => $rumah,
                ]
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan: ' . $e->getMessage());

            return response()->json([
                'error' => 'Terjadi kesalahan saat menyimpan data.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }


    
}
