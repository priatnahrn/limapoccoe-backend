<?php

namespace Modules\DataKependudukan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\DataKependudukanImport;
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
use Maatwebsite\Excel\Facades\Excel;
use Modules\DataKependudukan\Transformers\DataKependudukanResource;

class DataKependudukanController extends Controller
{

    // public function create(DataKeluargaRequest $request)
    // {
    //     $admin = JWTAuth::parseToken()->authenticate();

    //     if (!$admin) {
    //         return response()->json(['error' => 'User belum login.'], 401);
    //     }

    //     if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
    //         return response()->json(['error' => 'Tidak memiliki akses.'], 403);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // Simpan rumah baru dari input manual
    //         $rumah = Rumah::create([
    //             'id' => Str::uuid(),
    //             'no_rumah' => $request->no_rumah,
    //             'rt_rw' => $request->rt_rw,
    //             'dusun' => $request->dusun,
    //         ]);

    //         // Simpan keluarga
    //         $keluarga = Keluarga::create([
    //             'id' => Str::uuid(),
    //             'nomor_kk' => $request->nomor_kk,
    //             'rumah_id' => $rumah->id,
    //         ]);

    //         // Simpan anggota jika ada
    //         foreach ($request->input('anggota', []) as $index => $anggota) {
    //             Penduduk::create([
    //                 'keluarga_id' => $keluarga->id,
    //                 'nik' => $anggota['nik'],
    //                 'no_urut' => $anggota['no_urut'] ?? str_pad($index + 1, 2, '0', STR_PAD_LEFT),
    //                 'nama_lengkap' => $anggota['nama_lengkap'],
    //                 'hubungan' => $anggota['hubungan'] ?? null,
    //                 'tempat_lahir' => $anggota['tempat_lahir'] ?? null,
    //                 'tgl_lahir' => $anggota['tgl_lahir'] ?? null,
    //                 'jenis_kelamin' => $anggota['jenis_kelamin'] ?? null,
    //                 'status_perkawinan' => $anggota['status_perkawinan'] ?? null,
    //                 'agama' => $anggota['agama'] ?? null,
    //                 'pendidikan' => $anggota['pendidikan'] ?? null,
    //                 'pekerjaan' => $anggota['pekerjaan'] ?? null,
    //                 'no_bpjs' => $anggota['no_bpjs'] ?? null,
    //                 'nama_ayah' => $anggota['nama_ayah'] ?? null,
    //                 'nama_ibu' => $anggota['nama_ibu'] ?? null,
    //             ]);
    //         }


    //         DB::commit();

    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $admin->id,
    //             'activity_type' => 'create',
    //             'description' => 'Membuat data keluarga dan rumah baru.',
    //             'ip_address' => $request->ip(),
    //         ]);

    //         return response()->json([
    //             'message' => 'Data berhasil disimpan.',
    //             'data' => new DataKependudukanResource($keluarga->load('rumah', 'penduduks'))
    //         ], 200);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Gagal menyimpan: ' . $e->getMessage());

    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $admin->id,
    //             'activity_type' => 'create',
    //             'description' => 'Gagal menyimpan data keluarga dan rumah.',
    //             'ip_address' => $request->ip(),
    //         ]);

    //         return response()->json([
    //             'error' => 'Terjadi kesalahan saat menyimpan data.',
    //             'detail' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function create(DataKeluargaRequest $request)
    {
        // [SCP #23, #24, #28] - Authentication on server-side
        $admin = JWTAuth::parseToken()->authenticate();

        if (!$admin) {
            return response()->json(['error' => 'User belum login.'], 401);
        }

        // [SCP #77–#88] - Access control enforcement
        if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
            return response()->json(['error' => 'Tidak memiliki akses.'], 403);
        }

        DB::beginTransaction();

        try {
            // [SCP #1–#16] - Input validation assumed handled by DataKeluargaRequest

            // [SCP #167] - Use ORM (Eloquent) → safe parameterized query
            $rumah = Rumah::create([
                'id' => Str::uuid(),
                'no_rumah' => $request->no_rumah,
                'rt_rw' => $request->rt_rw,
                'dusun' => $request->dusun,
            ]);

            $keluarga = Keluarga::create([
                'id' => Str::uuid(),
                'nomor_kk' => $request->nomor_kk,
                'rumah_id' => $rumah->id,
            ]);

            // [SCP #14, #167] - Validate each anggota field
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

            // [SCP #127] - Audit log for activity
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

            // [SCP #108, #119] - Logging error securely
            Log::error('Gagal menyimpan data keluarga', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'create',
                'description' => 'Gagal menyimpan data keluarga dan rumah.',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat menyimpan data.',
                // Hindari mengekspos exception detail ke client di produksi
            ], 500);
        }
    }

    // public function getAllDataKependudukan()
    // {
    //     $admin = JWTAuth::parseToken()->authenticate();

    //     if (!$admin) {
    //         return response()->json(['error' => 'User belum login.'], 401);
    //     }

    //     if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
    //         return response()->json(['error' => 'Tidak memiliki akses.'], 403);
    //     }

    //     // ✅ Load rumah dan penduduks
    //     $dataKependudukan = Keluarga::with(['rumah', 'penduduks'])->get();

    //     if ($dataKependudukan->isEmpty()) {
    //         return response()->json(['message' => 'Tidak ada data kependudukan ditemukan.'], 404);
    //     }

    //     // ✅ Transformasi data menggunakan resource
    //     LogActivity::create([
    //         'id' => Str::uuid(),
    //         'user_id' => $admin->id,
    //         'activity_type' => 'read',
    //         'description' => 'Mengambil semua data kependudukan.'
    //     ]);

    //     return response()->json([
    //         'message' => 'Data kependudukan berhasil diambil.',
    //         'data' => DataKependudukanResource::collection($dataKependudukan),
    //     ], 200);
    // }


    public function getAllDataKependudukan()
    {
        try {
            // [SCP #23–#24] Validasi autentikasi
            $admin = JWTAuth::parseToken()->authenticate();

            if (!$admin) {
                // [SCP #122] Logging upaya login gagal
                Log::warning('Akses data kependudukan ditolak: user belum login');
                return response()->json(['error' => 'User belum login.'], 401);
            }

            // [SCP #77–#88] Validasi otorisasi
            if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
                Log::warning('Akses data kependudukan ditolak: tidak memiliki akses.', [
                    'user_id' => $admin->id
                ]);
                return response()->json(['error' => 'Tidak memiliki akses.'], 403);
            }

            // [SCP #167] ORM + eager loading → aman dari SQL Injection
            $dataKependudukan = Keluarga::with(['rumah', 'penduduks'])->get();

            if ($dataKependudukan->isEmpty()) {
                return response()->json(['message' => 'Tidak ada data kependudukan ditemukan.'], 404);
            }

            // [SCP #127] Audit log
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'read',
                'description' => 'Mengambil semua data kependudukan.'
            ]);

            return response()->json([
                'message' => 'Data kependudukan berhasil diambil.',
                'data' => DataKependudukanResource::collection($dataKependudukan),
            ], 200);
        } catch (\Throwable $e) {
            // [SCP #108–#110] Tangani kesalahan teknis dengan aman
            Log::error('Gagal mengambil data kependudukan', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data. Coba beberapa saat lagi.',
            ], 500);
        }
    }


    // public function getDetailDataKependudukan($id)
    // {
    //     $admin = JWTAuth::parseToken()->authenticate();

    //     if (!$admin) {
    //         return response()->json(['error' => 'User belum login.'], 401);
    //     }

    //     if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
    //         return response()->json(['error' => 'Tidak memiliki akses.'], 403);
    //     }

    //     $keluarga = Keluarga::with(['rumah', 'penduduks'])->find($id);

    //     if (!$keluarga) {
    //         return response()->json(['error' => 'Data keluarga tidak ditemukan.'], 404);
    //     }
    //     LogActivity::create([
    //         'id' => Str::uuid(),
    //         'user_id' => $admin->id,
    //         'activity_type' => 'read',
    //         'description' => 'Mengambil detail data keluarga.',
    //         'ip_address' => request()->ip(),
    //     ]);

    //     return response()->json([
    //         'message' => 'Detail data keluarga berhasil diambil.',
    //         'data' => new DataKependudukanResource($keluarga),
    //     ], 200);
    // }

    public function getDetailDataKependudukan($id)
    {
        try {
            // [SCP #23–#24] Validasi autentikasi JWT
            $admin = JWTAuth::parseToken()->authenticate();

            if (!$admin) {
                Log::warning('Akses detail keluarga ditolak: belum login');
                return response()->json(['error' => 'User belum login.'], 401);
            }

            // [SCP #77–#85] Validasi otorisasi
            if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
                Log::warning('Akses detail keluarga ditolak: role tidak sesuai', ['user_id' => $admin->id]);
                return response()->json(['error' => 'Tidak memiliki akses.'], 403);
            }

            // [SCP #11, #167] Validasi UUID jika pakai UUID, sesuaikan jika bukan
            if (!Str::isUuid($id)) {
                Log::notice('Permintaan dengan ID tidak valid', ['id' => $id, 'user_id' => $admin->id]);
                return response()->json(['error' => 'Format ID tidak valid.'], 400);
            }

            // [SCP #167] Gunakan ORM → parameterized query
            $keluarga = Keluarga::with(['rumah', 'penduduks'])->find($id);

            if (!$keluarga) {
                Log::info('Data keluarga tidak ditemukan', ['id' => $id, 'user_id' => $admin->id]);
                return response()->json(['error' => 'Data keluarga tidak ditemukan.'], 404);
            }

            // [SCP #127] Audit log aktivitas
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
        } catch (\Throwable $e) {
            // [SCP #110] Tangani error dengan aman
            Log::error('Gagal mengambil detail data keluarga', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil detail data keluarga. Coba beberapa saat lagi.',
            ], 500);
        }
    }

    // public function updateDataKependudukan(DataKeluargaRequest $request, $id)
    // {
    //     $admin = JWTAuth::parseToken()->authenticate();

    //     if (!$admin) {
    //         return response()->json(['error' => 'User belum login.'], 401);
    //     }

    //     if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
    //         return response()->json(['error' => 'Tidak memiliki akses.'], 403);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // Ambil data keluarga & rumah
    //         $keluarga = Keluarga::findOrFail($id);
    //         $rumah = Rumah::findOrFail($keluarga->rumah_id);

    //         // Update keluarga (hanya field yang dikirim)
    //         $keluarga->update(array_filter([
    //             'nomor_kk' => $request->input('nomor_kk'),
    //         ]));

    //         // Update rumah
    //         $rumah->update(array_filter([
    //             'no_rumah' => $request->input('no_rumah'),
    //             'rt_rw' => $request->input('rt_rw'),
    //             'dusun' => $request->input('dusun'),
    //         ]));

    //         // Update anggota keluarga (bulk add/update)
    //         $anggotaInput = collect($request->input('anggota', []));
    //         $updatedIds = [];

    //         foreach ($anggotaInput as $anggota) {
    //             if (!empty($anggota['id'])) {
    //                 $penduduk = Penduduk::where('id', $anggota['id'])->where('keluarga_id', $keluarga->id)->first();
    //                 if ($penduduk) {
    //                     $penduduk->update(array_filter([
    //                         'nik' => $anggota['nik'] ?? null,
    //                         'no_urut' => $anggota['no_urut'] ?? null,
    //                         'nama_lengkap' => $anggota['nama_lengkap'] ?? null,
    //                         'hubungan' => $anggota['hubungan'] ?? null,
    //                         'tempat_lahir' => $anggota['tempat_lahir'] ?? null,
    //                         'tgl_lahir' => $anggota['tgl_lahir'] ?? null,
    //                         'jenis_kelamin' => $anggota['jenis_kelamin'] ?? null,
    //                         'status_perkawinan' => $anggota['status_perkawinan'] ?? null,
    //                         'agama' => $anggota['agama'] ?? null,
    //                         'pendidikan' => $anggota['pendidikan'] ?? null,
    //                         'pekerjaan' => $anggota['pekerjaan'] ?? null,
    //                         'no_bpjs' => $anggota['no_bpjs'] ?? null,
    //                         'nama_ayah' => $anggota['nama_ayah'] ?? null,
    //                         'nama_ibu' => $anggota['nama_ibu'] ?? null,
    //                     ]));
    //                     $updatedIds[] = $penduduk->id;
    //                 }
    //             } else {
    //                 $new = Penduduk::create([
    //                     'keluarga_id' => $keluarga->id,
    //                     'nik' => $anggota['nik'],
    //                     'no_urut' => $anggota['no_urut'] ?? null,
    //                     'nama_lengkap' => $anggota['nama_lengkap'],
    //                     'hubungan' => $anggota['hubungan'] ?? null,
    //                     'tempat_lahir' => $anggota['tempat_lahir'] ?? null,
    //                     'tgl_lahir' => $anggota['tgl_lahir'] ?? null,
    //                     'jenis_kelamin' => $anggota['jenis_kelamin'] ?? null,
    //                     'status_perkawinan' => $anggota['status_perkawinan'] ?? null,
    //                     'agama' => $anggota['agama'] ?? null,
    //                     'pendidikan' => $anggota['pendidikan'] ?? null,
    //                     'pekerjaan' => $anggota['pekerjaan'] ?? null,
    //                     'no_bpjs' => $anggota['no_bpjs'] ?? null,
    //                     'nama_ayah' => $anggota['nama_ayah'] ?? null,
    //                     'nama_ibu' => $anggota['nama_ibu'] ?? null,
    //                 ]);
    //                 $updatedIds[] = $new->id;
    //             }
    //         }

    //         // [Opsional] Sinkronisasi: Hapus anggota yang tidak dikirim
    //         // Penduduk::where('keluarga_id', $keluarga->id)
    //         //     ->whereNotIn('id', $updatedIds)
    //         //     ->delete();

    //         DB::commit();

    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $admin->id,
    //             'activity_type' => 'update',
    //             'description' => 'Memperbarui data keluarga, rumah, dan anggota.',
    //             'ip_address' => $request->ip(),
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Data berhasil diperbarui.',
    //             'data' => new DataKependudukanResource($keluarga->load(['rumah', 'penduduks'])),
    //         ]);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Gagal update: ' . $e->getMessage());

    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $admin->id,
    //             'activity_type' => 'update',
    //             'description' => 'Gagal update data keluarga.',
    //             'ip_address' => $request->ip(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'error' => 'Terjadi kesalahan saat memperbarui data.',
    //             'detail' => config('app.debug') ? $e->getMessage() : null,
    //         ], 500);
    //     }
    // }

    public function updateDataKependudukan(DataKeluargaRequest $request, $id)
    {
        try {
            // 🔐 [SCP #23, ASVS 2.1.1] – Autentikasi wajib di sisi server
            $admin = JWTAuth::parseToken()->authenticate();

            // 🛡️ [SCP #24, ASVS 2.1.3] – Validasi bahwa token valid
            if (!$admin) {
                Log::warning('Update ditolak: belum login');
                return response()->json(['error' => 'User belum login.'], 401);
            }

            // 🛡️ [SCP #77–78, ASVS 2.2.1] – Validasi peran pengguna (otorisasi)
            if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
                Log::warning('Update ditolak: tidak punya hak akses', ['user_id' => $admin->id]);
                return response()->json(['error' => 'Tidak memiliki akses.'], 403);
            }

            // 🧱 [SCP #11, #14, ASVS 5.1.2] – Validasi format input ID (misalnya UUID)
            if (!Str::isUuid($id)) {
                return response()->json(['error' => 'Format ID tidak valid.'], 400);
            }

            DB::beginTransaction();

            // 🔒 [SCP #167, ASVS 5.2.2] – Akses basis data menggunakan Eloquent (ORM, parameterized)
            $keluarga = Keluarga::findOrFail($id);
            $rumah = Rumah::findOrFail($keluarga->rumah_id);

            // 🧹 [SCP #14, #133, ASVS 5.3.4] – Gunakan whitelist (pastikan `$fillable` aman)
            $keluarga->update([
                'nomor_kk' => $request->input('nomor_kk'),
            ]);

            $rumah->update([
                'no_rumah' => $request->input('no_rumah'),
                'rt_rw' => $request->input('rt_rw'),
                'dusun' => $request->input('dusun'),
            ]);

            $anggotaInput = collect($request->input('anggota', []));
            $updatedIds = [];

            foreach ($anggotaInput as $anggota) {
                // 📛 [SCP #11, ASVS 5.3.2] – Validasi ID sebelum update
                if (!empty($anggota['id'])) {
                    $penduduk = Penduduk::where('id', $anggota['id'])
                        ->where('keluarga_id', $keluarga->id)
                        ->first();

                    if ($penduduk) {
                        $penduduk->update([
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
                        ]);
                        $updatedIds[] = $penduduk->id;
                    }
                } else {
                    // 🆕 [SCP #1–#14, ASVS 5.1.1] – Validasi input sebelum create
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

            DB::commit();

            // 📝 [SCP #127, ASVS 7.5.1] – Logging aktivitas administratif
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

            // 🚨 [SCP #108–109, ASVS 10.3.1] – Tangani error dengan log internal, jangan tampilkan detail ke user
            Log::error('Gagal update data keluarga', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
            ], 500);
        }
    }



    // public function deleteAnggotaKeluarga($id)
    // {
    //     $admin = JWTAuth::parseToken()->authenticate();

    //     if (!$admin) {
    //         return response()->json(['error' => 'User belum login.'], 401);
    //     }

    //     if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
    //         return response()->json(['error' => 'Tidak memiliki akses.'], 403);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $penduduk = Penduduk::findOrFail($id);
    //         $penduduk->delete(); // Hapus penduduk

    //         DB::commit();

    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $admin->id,
    //             'activity_type' => 'delete',
    //             'description' => 'Menghapus data penduduk.',
    //             'ip_address' => request()->ip(),
    //         ]);

    //         return response()->json(['message' => 'Data penduduk berhasil dihapus.'], 200);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Gagal menghapus data: ' . $e->getMessage());

    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $admin->id,
    //             'activity_type' => 'delete',
    //             'description' => 'Gagal menghapus data penduduk.',
    //             'ip_address' => request()->ip(),
    //         ]);

    //         return response()->json([
    //             'error' => 'Terjadi kesalahan saat menghapus data.',
    //             'detail' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function deleteAnggotaKeluarga($id)
    {
        try {
            // 🔐 [SCP #23, ASVS 2.1.1] – Autentikasi JWT
            $admin = JWTAuth::parseToken()->authenticate();

            // 🛡️ [SCP #24, ASVS 2.1.3] – Cek login status
            if (!$admin) {
                Log::warning('Hapus penduduk ditolak: belum login');
                return response()->json(['error' => 'User belum login.'], 401);
            }

            // 🛡️ [SCP #77, ASVS 2.2.1] – Validasi hak akses
            if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
                Log::warning('Hapus penduduk ditolak: tidak punya akses', ['user_id' => $admin->id]);
                return response()->json(['error' => 'Tidak memiliki akses.'], 403);
            }

            // 🧱 [SCP #11, ASVS 5.1.2] – Validasi ID (UUID)
            if (!Str::isUuid($id)) {
                return response()->json(['error' => 'Format ID tidak valid.'], 400);
            }

            DB::beginTransaction();

            // 🔒 [SCP #167, ASVS 5.2.2] – Gunakan ORM untuk keamanan query
            $penduduk = Penduduk::findOrFail($id);
            $penduduk->delete();

            DB::commit();

            // 📝 [SCP #127, ASVS 7.5.1] – Audit log penghapusan
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

            // 🚨 [SCP #109–110, ASVS 10.3.1] – Hindari expose error ke client, log secara internal
            Log::error('Gagal menghapus data penduduk', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 📝 [SCP #127] – Log aktivitas gagal
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id ?? null,
                'activity_type' => 'delete',
                'description' => 'Gagal menghapus data penduduk.',
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat menghapus data.',
            ], 500);
        }
    }


    // public function deleteDataKeluarga($id)
    // {
    //     $admin = JWTAuth::parseToken()->authenticate();

    //     if (!$admin) {
    //         return response()->json(['error' => 'User belum login.'], 401);
    //     }

    //     if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
    //         return response()->json(['error' => 'Tidak memiliki akses.'], 403);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $keluarga = Keluarga::findOrFail($id);
    //         $keluarga->penduduks()->delete(); // Hapus semua penduduk terkait
    //         $keluarga->delete(); // Hapus keluarga

    //         DB::commit();

    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $admin->id,
    //             'activity_type' => 'delete',
    //             'description' => 'Menghapus data keluarga dan rumah.',
    //             'ip_address' => request()->ip(),
    //         ]);

    //         return response()->json(['message' => 'Data keluarga berhasil dihapus.'], 200);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Gagal menghapus data: ' . $e->getMessage());

    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $admin->id,
    //             'activity_type' => 'delete',
    //             'description' => 'Gagal menghapus data keluarga dan rumah.',
    //             'ip_address' => request()->ip(),
    //         ]);

    //         return response()->json([
    //             'error' => 'Terjadi kesalahan saat menghapus data.',
    //             'detail' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function deleteDataKeluarga($id)
    {
        try {
            // 🔐 [SCP #23–24, ASVS 2.1.1] – Validasi autentikasi
            $admin = JWTAuth::parseToken()->authenticate();

            if (!$admin) {
                Log::warning('Hapus keluarga ditolak: belum login');
                return response()->json(['error' => 'User belum login.'], 401);
            }

            // 🛡️ [SCP #77–78, ASVS 2.2.1] – Validasi hak akses
            if (!$admin->hasAnyRole(['super-admin', 'staff-desa'])) {
                Log::warning('Hapus keluarga ditolak: tidak punya akses', ['user_id' => $admin->id]);
                return response()->json(['error' => 'Tidak memiliki akses.'], 403);
            }

            // 🧱 [SCP #11, ASVS 5.1.2] – Validasi format ID (jika UUID)
            if (!Str::isUuid($id)) {
                return response()->json(['error' => 'Format ID tidak valid.'], 400);
            }

            DB::beginTransaction();

            // 🔒 [SCP #167, ASVS 5.2.2] – Gunakan ORM aman dari injection
            $keluarga = Keluarga::findOrFail($id);

            // 🧹 [ASVS 8.3.3] – Hapus dependensi terkait sebelum entitas utama
            $keluarga->penduduks()->delete();
            $keluarga->delete();

            DB::commit();

            // 📝 [SCP #127, ASVS 7.5.1] – Audit log aktivitas
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

            // 🚨 [SCP #108–110, ASVS 10.3.1] – Log structured, tidak tampilkan error ke user
            Log::error('Gagal menghapus data keluarga', [
                'user_id' => $admin->id ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id ?? null,
                'activity_type' => 'delete',
                'description' => 'Gagal menghapus data keluarga dan rumah.',
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat menghapus data.',
            ], 500);
        }
    }



    // public function importExcel(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|file|mimes:xlsx,xls',
    //     ]);

    //     try {
    //         Excel::import(new DataKependudukanImport, $request->file('file'));
    //         return response()->json(['message' => 'Import berhasil']);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'error' => 'Gagal import',
    //             'detail' => config('app.debug') ? $e->getMessage() : null,
    //         ], 500);
    //     }
    // }

    public function importExcel(Request $request)
    {
        // 🔐 [SCP #23, ASVS 2.1.1] – Validasi autentikasi user (wajib untuk fungsi penting)
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login.'], 401);
        }

        // 🛡️ [SCP #77–78, ASVS 2.2.1] – Cek hak akses
        if (!$user->hasAnyRole(['super-admin', 'staff-desa'])) {
            return response()->json(['error' => 'Tidak memiliki akses.'], 403);
        }

        // 🧪 [SCP #11, #183, ASVS 5.1.4] – Validasi file input
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:2048', // max 2MB
        ]);

        try {
            // 🗂️ [SCP #184–#186] – Gunakan library import yang aman, lakukan validasi data di kelas import
            Excel::import(new DataKependudukanImport($user), $request->file('file'));

            // 📝 [SCP #127, ASVS 7.5.1] – Log aktivitas
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'import',
                'description' => 'Import data kependudukan via Excel.',
                'ip_address' => $request->ip(),
            ]);

            return response()->json(['message' => 'Import berhasil']);
        } catch (\Throwable $e) {
            // 🛑 [SCP #108–110, ASVS 10.3.1] – Jangan expose error teknis, log internal
            Log::error('Gagal import Excel', [
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Gagal import.',
            ], 500);
        }
    }
}
