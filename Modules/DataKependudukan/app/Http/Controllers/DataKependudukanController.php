<?php

namespace Modules\DataKependudukan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\DataKependudukan\Http\Requests\DataKeluargaRequest;
use Modules\DataKependudukan\Models\Rumah;
use Tymon\JWTAuth\Facades\JWTAuth;
use Modules\DataKependudukan\Models\Keluarga;
use Modules\DataKependudukan\Models\Penduduk;
use Illuminate\Support\Str;
use Modules\DataKependudukan\Transformers\DataKependudukanResource;

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

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'create',
                'description' => 'Membuat data keluarga dan rumah baru.',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Data berhasil disimpan.',
                'data' => new DataKependudukanResource($keluarga->load('rumah', 'penduduks'))
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan: ' . $e->getMessage());

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'create',
                'description' => 'Gagal menyimpan data keluarga dan rumah.',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat menyimpan data.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllDataKependudukan(Request $request)
    {
        $admin = JWTAuth::parseToken()->authenticate();

        if (!$admin) {
            return response()->json(['error' => 'User belum login.'], 401);
        }

        if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
            return response()->json(['error' => 'Tidak memiliki akses.'], 403);
        }

        // ✅ Load rumah dan penduduks
        $dataKependudukan = Keluarga::with(['rumah', 'penduduks'])->get();

        if ($dataKependudukan->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data kependudukan ditemukan.'], 404);
        }

        // ✅ Transformasi data menggunakan resource
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $admin->id,
            'activity_type' => 'read',
            'description' => 'Mengambil semua data kependudukan.',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Data kependudukan berhasil diambil.',
            'data' => DataKependudukanResource::collection($dataKependudukan),
        ], 200);
    }

    public function getDetailDataKependudukan($id)
    {
        $admin = JWTAuth::parseToken()->authenticate();

        if (!$admin) {
            return response()->json(['error' => 'User belum login.'], 401);
        }

        if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
            return response()->json(['error' => 'Tidak memiliki akses.'], 403);
        }

        $keluarga = Keluarga::with(['rumah', 'penduduks'])->find($id);

        if (!$keluarga) {
            return response()->json(['error' => 'Data keluarga tidak ditemukan.'], 404);
        }
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $admin->id,
            'activity_type' => 'read',
            'description' => 'Mengambil detail data keluarga.',
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Detail data keluarga berhasil diambil.',
            'data' => new DataKependudukanResource($keluarga),
        ], 200);
    }

    public function updateDataKependudukan(DataKeluargaRequest $request, $id)
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
            // Ambil data keluarga & rumah
            $keluarga = Keluarga::findOrFail($id);
            $rumah = Rumah::findOrFail($keluarga->rumah_id);

            // Update keluarga (hanya field yang dikirim)
            $keluarga->update(array_filter([
                'nomor_kk' => $request->input('nomor_kk'),
            ]));

            // Update rumah
            $rumah->update(array_filter([
                'no_rumah' => $request->input('no_rumah'),
                'rt_rw' => $request->input('rt_rw'),
                'dusun' => $request->input('dusun'),
            ]));

            // Update anggota keluarga (bulk add/update)
            $anggotaInput = collect($request->input('anggota', []));
            $updatedIds = [];

            foreach ($anggotaInput as $anggota) {
                if (!empty($anggota['id'])) {
                    $penduduk = Penduduk::where('id', $anggota['id'])->where('keluarga_id', $keluarga->id)->first();
                    if ($penduduk) {
                        $penduduk->update(array_filter([
                            'nik' => $anggota['nik'] ?? null,
                            'no_urut' => $anggota['no_urut'] ?? null,
                            'nama_lengkap' => $anggota['nama_lengkap'] ?? null,
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
                        ]));
                        $updatedIds[] = $penduduk->id;
                    }
                } else {
                    $new = Penduduk::create([
                        'keluarga_id' => $keluarga->id,
                        'nik' => $anggota['nik'],
                        'no_urut' => $anggota['no_urut'] ?? null,
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
                    $updatedIds[] = $new->id;
                }
            }

            // [Opsional] Sinkronisasi: Hapus anggota yang tidak dikirim
            // Penduduk::where('keluarga_id', $keluarga->id)
            //     ->whereNotIn('id', $updatedIds)
            //     ->delete();

            DB::commit();

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'update',
                'description' => 'Memperbarui data keluarga, rumah, dan anggota.',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui.',
                'data' => new DataKependudukanResource($keluarga->load(['rumah', 'penduduks'])),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal update: ' . $e->getMessage());

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'update',
                'description' => 'Gagal update data keluarga.',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan saat memperbarui data.',
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function deleteAnggotaKeluarga($id)
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
            $penduduk = Penduduk::findOrFail($id);
            $penduduk->delete(); // Hapus penduduk

            DB::commit();

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'delete',
                'description' => 'Menghapus data penduduk.',
                'ip_address' => request()->ip(),
            ]);

            return response()->json(['message' => 'Data penduduk berhasil dihapus.'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menghapus data: ' . $e->getMessage());

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'delete',
                'description' => 'Gagal menghapus data penduduk.',
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat menghapus data.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteDataKeluarga($id)
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
            $keluarga = Keluarga::findOrFail($id);
            $keluarga->penduduks()->delete(); // Hapus semua penduduk terkait
            $keluarga->delete(); // Hapus keluarga

            DB::commit();

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'delete',
                'description' => 'Menghapus data keluarga dan rumah.',
                'ip_address' => request()->ip(),
            ]);

            return response()->json(['message' => 'Data keluarga berhasil dihapus.'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menghapus data: ' . $e->getMessage());

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'delete',
                'description' => 'Gagal menghapus data keluarga dan rumah.',
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat menghapus data.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }
}
