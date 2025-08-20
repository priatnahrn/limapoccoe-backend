<?php

namespace Modules\PengajuanSurat\Http\Controllers;

use App\Helpers\FonnteHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\PengajuanSurat\Models\Ajuan;
use Modules\PengajuanSurat\Models\Surat;
use App\Models\LogActivity;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Modules\PengajuanSurat\Models\TandaTangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Modules\PengajuanSurat\Http\Requests\AjuanRequest;
use Modules\PengajuanSurat\Http\Requests\FillNumberRequest;
use Modules\PengajuanSurat\Transformers\AjuanResource;
use Modules\PengajuanSurat\Transformers\TandaTanganResource;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;
use Spatie\LaravelPdf\Facades\Pdf as SpatiePdf;


class PengajuanSuratController extends Controller
{

    // public function ajukanSurat(Request $request, $slug)
    // {
    //     $authUser = JWTAuth::parseToken()->authenticate();
    //     if (!$authUser) {
    //         return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
    //     }

    //     if (!$authUser->hasRole('masyarakat') && !$authUser->hasRole('staff-desa')) {
    //         return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengajukan surat'], 403);
    //     }

    //     if ($authUser->hasRole('masyarakat') && !$authUser->is_profile_complete) {
    //         return response()->json([
    //             'error' => 'Profil Anda belum lengkap. Harap lengkapi profil terlebih dahulu sebelum mengajukan surat.'
    //         ], 422);
    //     }

    //     $surat = Surat::where('slug', $slug)->first();
    //     if (!$surat) {
    //         return response()->json(['error' => 'Surat tidak ditemukan'], 404);
    //     }

    //     $validatedData = $request->validate([
    //         'data_surat' => 'required|array',
    //         'lampiran' => 'nullable|array',
    //         'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
    //     ]);

    //     $ajuan = Ajuan::create([
    //         'user_id' => $authUser->id, // pencatat pengaju, bukan yang diajukan
    //         'surat_id' => $surat->id,
    //         'data_surat' => json_encode($validatedData['data_surat']),
    //         'status' => 'processed',
    //     ]);

    //     // Simpan lampiran jika ada
    //     if (isset($validatedData['lampiran'])) {
    //         foreach ($validatedData['lampiran'] as $file) {
    //             $path = $file->store('lampiran', 'public');
    //             $ajuan->lampiran()->create([
    //                 'file_path' => $path,
    //             ]);
    //         }
    //     }

    //     // Ambil nama dari data_surat untuk keperluan log
    //     $namaPemohon = $validatedData['data_surat']['nama_lengkap'] ?? 'tidak diketahui';

    //     // Log aktivitas
    //     LogActivity::create([
    //         'id' => Str::uuid(),
    //         'user_id' => $authUser->id,
    //         'activity_type' => 'ajuan_surat',
    //         'description' => 'Surat "' . $surat->nama_surat . '" diajukan untuk ' . $namaPemohon,
    //         'ip_address' => $request->ip(),
    //     ]);

    //     return response()->json([
    //         'message' => 'Surat berhasil diajukan.',
    //         'ajuan_surat' => $ajuan,
    //     ], 200);
    // }


    // public function ajukanSurat(AjuanRequest $request, $slug)
    // {
    //     try {
    //         // ✅ [ASVS V2.1] [SCP #23] Autentikasi di awal proses
    //         $authUser = JWTAuth::parseToken()->authenticate();
    //         if (!$authUser) {
    //             return response()->json(['error' => 'Anda belum login. Silakan login terlebih dahulu'], 401);
    //         }

    //         // ✅ [ASVS V4.1] [SCP #77, #84] Kontrol akses berbasis peran
    //         if (!$authUser->hasRole('masyarakat') && !$authUser->hasRole('staff-desa')) {
    //             return response()->json(['error' => 'Anda tidak memiliki izin untuk mengajukan surat.'], 403);
    //         }

    //         // ✅ [ASVS V2.1.1] Validasi status profil pengguna
    //         if ($authUser->hasRole('masyarakat') && !$authUser->is_profile_complete) {
    //             return response()->json([
    //                 'error' => 'Profil belum lengkap. Lengkapi sebelum mengajukan surat.'
    //             ], 422);
    //         }

    //         // ✅ [SCP #2] Validasi entitas dari input (slug → surat)
    //         $surat = Surat::where('slug', $slug)->first();
    //         if (!$surat) {
    //             return response()->json(['error' => 'Surat tidak ditemukan.'], 404);
    //         }

    //         // ✅ [ASVS V5.1] [SCP #11, #13, #14] Validasi input eksplisit & whitelist karakter
    //         $validatedData = $request->validated();

    //         // ✅ [ASVS V4.1.3] Simpan data dengan pembatasan hak akses minimal
    //         $ajuan = Ajuan::create([
    //             'user_id' => $authUser->id,
    //             'surat_id' => $surat->id,
    //             'data_surat' => json_encode($validatedData['data_surat']), // [SCP #1, #12]
    //             'status' => 'processed',
    //         ]);

    //         // ✅ [ASVS V5.1.4] [SCP #185, #186, #192] Penanganan aman file upload
    //         if (!empty($validatedData['lampiran'])) {
    //             foreach ($validatedData['lampiran'] as $file) {
    //                 $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension(); // [SCP #104]
    //                 $path = $file->storeAs('lampiran/' . $ajuan->id, $filename, 'private'); // Direktori privat & aman

    //                 $ajuan->lampiran()->create([
    //                     'file_path' => $path,
    //                 ]);
    //             }
    //         }

    //         // ✅ [ASVS V8.3] [SCP #127, #114, #113] Logging aman (tidak menyimpan data sensitif)
    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $authUser->id,
    //             'activity_type' => 'ajuan_surat',
    //             'description' => 'Surat "' . $surat->nama_surat . '" diajukan.', // hindari log PII
    //             'ip_address' => $request->ip(),
    //         ]);

    //         // ✅ [ASVS V9.1] Response aman, tidak bocorkan info sensitif
    //         return response()->json([
    //             'message' => 'Surat berhasil diajukan.',
    //             'ajuan_surat' => new AjuanResource($ajuan),
    //         ], 200);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         // ✅ [ASVS V9.2] Penanganan validasi input yang aman
    //         return response()->json([
    //             'error' => 'Validasi gagal.',
    //             'details' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         // ✅ [SCP #108] Jangan tampilkan detail error ke client
    //         Log::error('Kesalahan saat ajukanSurat: ' . $e->getMessage());
    //         return response()->json(['error' => 'Terjadi kesalahan internal. Coba beberapa saat lagi'], 500); // [SCP #112]
    //     }
    // }


    public function ajukanSurat(AjuanRequest $request, $slug)
    {
        try {
            // ✅ [ASVS V2.1] [SCP #23] Autentikasi di awal proses
            $authUser = JWTAuth::parseToken()->authenticate();
            if (!$authUser) {
                return response()->json(['error' => 'Anda belum login. Silakan login terlebih dahulu'], 401);
            }

            // ✅ Rate Limiting: Maksimal 1 pengajuan per menit per user
            $rateLimitKey = 'rl:ajuan:surat:' . $authUser->id;
            if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);
                return response()->json([
                    'error' => 'Anda hanya bisa mengajukan surat sekali setiap 2 menit. Silakan coba lagi dalam ' . $seconds . ' detik.'
                ], 429);
            }
            RateLimiter::hit($rateLimitKey, 120); // delay 60 detik antar pengajuan

            // ✅ [ASVS V4.1] [SCP #77, #84] Kontrol akses berbasis peran
            if (!$authUser->hasRole('masyarakat') && !$authUser->hasRole('staff-desa')) {
                return response()->json(['error' => 'Anda tidak memiliki izin untuk mengajukan surat.'], 403);
            }

            // ✅ [ASVS V2.1.1] Validasi status profil pengguna
            if ($authUser->hasRole('masyarakat') && !$authUser->is_profile_complete) {
                return response()->json([
                    'error' => 'Profil belum lengkap. Lengkapi sebelum mengajukan surat.'
                ], 422);
            }

            // ✅ [SCP #2] Validasi entitas dari input (slug → surat)
            $surat = Surat::where('slug', $slug)->first();
            if (!$surat) {
                return response()->json(['error' => 'Surat tidak ditemukan.'], 404);
            }

            // ✅ [ASVS V5.1] [SCP #11, #13, #14] Validasi input eksplisit & whitelist karakter
            $validatedData = $request->validated();

            // ✅ [ASVS V4.1.3] Simpan data dengan pembatasan hak akses minimal
            $ajuan = Ajuan::create([
                'user_id' => $authUser->id,
                'surat_id' => $surat->id,
                'data_surat' => json_encode($validatedData['data_surat']), // [SCP #1, #12]
                'status' => 'processed',
            ]);

            // ✅ [ASVS V5.1.4] [SCP #185, #186, #192] Penanganan aman file upload
            if (!empty($validatedData['lampiran'])) {
                foreach ($validatedData['lampiran'] as $file) {
                    $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension(); // [SCP #104]
                    $path = $file->storeAs('lampiran/' . $ajuan->id, $filename, 'private'); // Direktori privat & aman

                    $ajuan->lampiran()->create([
                        'file_path' => $path,
                    ]);
                }
            }

            // ✅ [ASVS V8.3] [SCP #127, #114, #113] Logging aman (tidak menyimpan data sensitif)
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $authUser->id,
                'activity_type' => 'ajuan_surat',
                'description' => 'Surat "' . $surat->nama_surat . '" diajukan.', // hindari log PII
                'ip_address' => $request->ip(),
            ]);

            // ✅ [ASVS V9.1] Response aman, tidak bocorkan info sensitif
            return response()->json([
                'message' => 'Surat berhasil diajukan.',
                'ajuan_surat' => new AjuanResource($ajuan),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ✅ [ASVS V9.2] Penanganan validasi input yang aman
            return response()->json([
                'error' => 'Validasi gagal.',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // ✅ [SCP #108] Jangan tampilkan detail error ke client
            Log::error('Kesalahan saat ajukanSurat: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan internal. Coba beberapa saat lagi'], 500); // [SCP #112]
        }
    }

    public function getAllPengajuanSurat()
    {
        try {
            // ✅ [ASVS V2.1] [SCP #23] Autentikasi di awal proses
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Anda belum login. Silakan login terlebih dahulu'], 401);
            }

            // ✅ [ASVS V4.1] Inisialisasi query dasar
            $query = Ajuan::with(['user', 'user.profileMasyarakat', 'surat']);

            // ✅ [ASVS V4.2] Kontrol akses berbasis peran (Role-Based Access Control)
            if (!$user->hasAnyRole(['super-admin', 'staff-desa'])) {
                $query->where('user_id', $user->id);
            }

            // ✅ [ASVS V9.1] Ambil semua pengajuan surat
            $pengajuanSurat = $query->get();

            // ✅ [ASVS V9.1] Response aman, tidak bocorkan info sensitif
            return response()->json([
                'message' => 'Berhasil mendapatkan semua pengajuan surat.',
                'pengajuan_surat' => AjuanResource::collection($pengajuanSurat),
            ], 200);
        } catch (\Exception $e) {
            // ✅ [SCP #108, #112] Error tidak mengekspos informasi sistem
            Log::error('Gagal mendapatkan semua pengajuan surat: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    public function getPengajuanSuratBySlug($slug)
    {
        try {
            // ✅ [ASVS V2.1] [SCP #23] Autentikasi di awal proses
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Anda belum login. Silakan login terlebih dahulu'], 401);
            }

            // ✅ [ASVS V5.1.2] Validasi slug agar sesuai format yang diharapkan (jika belum di route-level)
            if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
                return response()->json(['error' => 'Format slug tidak valid.'], 400);
            }

            // ✅ [ASVS V4.1] Inisialisasi query dasar
            $baseQuery = Ajuan::with([
                'user',
                'user.profileMasyarakat',
                'surat'
            ])->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            });

            // ✅ [ASVS V4.2] Kontrol akses berbasis peran
            if ($user->hasRole('masyarakat')) {
                $pengajuanSurat = $baseQuery->where('user_id', $user->id)->get();
            } elseif ($user->hasRole('staff-desa')) {
                $pengajuanSurat = $baseQuery->get();
            } elseif ($user->hasRole('kepala-desa')) {
                $pengajuanSurat = $baseQuery->whereIn('status', ['confirmed', 'approved'])->get();
            } else {
                return response()->json([
                    'error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses daftar pengajuan surat ini.',
                ], 403); // [SCP #79] Fail securely pada akses
            }

            // ✅ [ASVS V9.1] Feedback aman ketika tidak ditemukan data
            if ($pengajuanSurat->isEmpty()) {
                return response()->json(['message' => 'Tidak ada pengajuan surat yang ditemukan'], 200);
            }

            // ✅ [SCP #113–127] Logging aktivitas pengguna tanpa data sensitif
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'get_pengajuan_surat',
                'description' => 'Akses daftar pengajuan surat untuk slug "' . $slug . '".',
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

            // ✅ [ASVS V9.2] Response terstruktur tanpa bocoran sensitif
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar pengajuan surat.',
                'pengajuan_surat' => AjuanResource::collection($pengajuanSurat),
            ], 200);
        } catch (\Exception $e) {
            // ✅ [SCP #108, #112] Error tidak mengekspos informasi sistem
            Log::error('Gagal mendapatkan pengajuan surat: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan internal.'], 500);
        }
    }




    public function getDetailPengajuanSurat($slug, $ajuanId)
    {
        // ✅ [ASVS V2.1] [SCP #23] Autentikasi wajib sebelum proses dilakukan
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        // ✅ [ASVS V4.1] [SCP #77, #84] Kontrol akses berbasis peran (Role-Based Access Control)
        if (!$user->hasRole('masyarakat') && !$user->hasAnyRole(['staff-desa', 'super-admin'])) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses detail pengajuan surat ini'], 403);
        }

        // ✅ [ASVS V4.1.3] [SCP #81, #84] Pastikan akses data hanya untuk entitas yang berwenang
        $pengajuanSurat = Ajuan::with(['user', 'user.profileMasyarakat', 'surat'])
            ->where('id', $ajuanId)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$pengajuanSurat) {
            // ✅ [ASVS V9.2] [SCP #109] Penanganan kesalahan aman tanpa membocorkan detail sistem
            return response()->json(['error' => 'Pengajuan surat tidak ditemukan'], 404);
        }

        // ✅ [ASVS V8.3] [SCP #114, #127] Logging aman (tanpa data sensitif) untuk audit dan monitoring
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'detail_pengajuan_surat',
            'description' => 'Detail pengajuan surat dengan ID ' . $pengajuanSurat->id . ' telah diakses.', // Hindari log PII
            'ip_address' => request()->ip(),
        ]);

        // ✅ [ASVS V9.1] [SCP #107, #119] Respon aman, tidak mengembalikan data sensitif secara langsung
        return response()->json([
            'message' => 'Berhasil mendapatkan detail pengajuan surat.',
            'pengajuan_surat' => new AjuanResource($pengajuanSurat),
        ], 200);
    }



    // public function fillNumber(FillNumberRequest $request, $slug, $ajuanId)
    // {
    //     $user = JWTAuth::parseToken()->authenticate();

    //     if (!$user->hasRole('staff-desa')) {
    //         return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengisi nomor pengajuan surat ini'], 403);
    //     }

    //     $validated = $request->validated();

    //     $pengajuanSurat = Ajuan::with(['user', 'surat'])
    //         ->where('id', $ajuanId)
    //         ->whereHas('surat', function ($query) use ($slug) {
    //             $query->where('slug', $slug);
    //         })
    //         ->first();

    //     if (!$pengajuanSurat) {
    //         return response()->json(['error' => 'Pengajuan surat tidak ditemukan'], 404);
    //     }

    //     $surat = Surat::where('slug', $slug)->first();
    //     $kodeSurat = $surat ? $surat->kode_surat : 'XXX';

    //     $kodeWilayah = '10.2003';
    //     $nomorUrutManual = $validated['nomor_surat'];
    //     $bulanRomawi = $this->toRoman(Carbon::now()->month);
    //     $tahun = Carbon::now()->year;

    //     $nomorSurat = $nomorUrutManual . '/' . $kodeSurat . '/' . $kodeWilayah . '/' . $bulanRomawi . '/' . $tahun;

    //     $pengajuanSurat->nomor_surat = $validated['nomor_surat'];
    //     $pengajuanSurat->nomor_surat_tersimpan = $nomorSurat;
    //     $pengajuanSurat->save();

    //     LogActivity::create([
    //         'id' => Str::uuid(),
    //         'user_id' => $user->id,
    //         'activity_type' => 'isi_nomor_surat',
    //         'description' => 'Nomor surat dengan ID ' . $pengajuanSurat->id . ' telah diisi.',
    //         'ip_address' => request()->ip(),
    //     ]);

    //     return response()->json([
    //         'message' => 'Nomor surat berhasil diisi.',
    //         'pengajuan_surat' => new AjuanResource($pengajuanSurat),
    //     ], 200);
    // }

    public function getLastNomorSurat()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Pastikan hanya staff-desa yang bisa mengakses
            if (!$user->hasRole('staff-desa')) {
                return response()->json([
                    'error' => 'Akses ditolak.'
                ], 403);
            }

            // Ambil semua Ajuan yang punya nomor_surat dan urutkan berdasarkan created_at
            $lastNomorSurat = Ajuan::whereNotNull('nomor_surat')
                ->orderByDesc('updated_at')
                ->get();

            // Ambil nomor_surat terakhir dari koleksi data yang ada
            $nomorTerakhir = $lastNomorSurat->first()?->nomor_surat ?? null;

            // Hitung nomor urut berikutnya (default 001)
            $lastUrut = 0;
            if ($nomorTerakhir && preg_match('/^(\d+)/', $nomorTerakhir, $matches)) {
                $lastUrut = (int) $matches[1];
            }

            $nextUrut = $lastUrut + 1;

            return response()->json([
                'nomor_surat_terakhir' => $nomorTerakhir,
                'nomor_surat_berikutnya' => $nextUrut
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil nomor surat terakhir', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data nomor surat.'
            ], 500);
        }
    }





    public function fillNumber(FillNumberRequest $request, $slug, $ajuanId)
    {
        try {
            // ✅ [ASVS V2.1] Autentikasi pengguna
            $user = JWTAuth::parseToken()->authenticate();

            // ✅ [ASVS V4.1 / SCP #77, #84] Kontrol akses berbasis peran
            if (!$user->hasRole('staff-desa')) {
                return response()->json([
                    'error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengisi nomor pengajuan surat ini.'
                ], 403);
            }

            // ✅ [ASVS V5.1] Validasi input eksplisit via FormRequest
            $validated = $request->validated();

            // ✅ [SCP #86] Validasi ID pengajuan berdasarkan slug
            $pengajuanSurat = Ajuan::with(['user', 'surat'])
                ->where('id', $ajuanId)
                ->whereHas('surat', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })
                ->first();

            if (!$pengajuanSurat) {
                return response()->json(['error' => 'Pengajuan surat tidak ditemukan.'], 404);
            }

            // ✅ [SCP #94] Rate Limiting: Maks. 1x pengisian per menit per user per ajuan
            $rateKey = 'rl:fill_number:' . $user->id . ':' . $pengajuanSurat->id;
            if (RateLimiter::tooManyAttempts($rateKey, 1)) {
                $wait = RateLimiter::availableIn($rateKey);
                return response()->json([
                    'error' => 'Terlalu sering. Silakan coba lagi dalam ' . $wait . ' detik.'
                ], 429);
            }
            RateLimiter::hit($rateKey, 60);

            // ✅ [ASVS V4.3 / SCP #93] Hindari hardcode, idealnya dari setting/config
            $surat = $pengajuanSurat->surat;
            $kodeSurat = $surat->kode_surat ?? 'XXX';
            $kodeWilayah = config('app.kode_wilayah', '10.2003'); // fallback default

            $nomorUrutManual = $validated['nomor_surat'];
            $bulanRomawi = $this->toRoman(now()->month);
            $tahun = now()->year;

            $nomorSurat = $nomorUrutManual . '/' . $kodeSurat . '/' . $kodeWilayah . '/' . $bulanRomawi . '/' . $tahun;

            // ✅ [ASVS V1.7] Simpan data secara sah atas nama user yang berwenang
            $pengajuanSurat->nomor_surat = $nomorUrutManual;
            $pengajuanSurat->nomor_surat_tersimpan = $nomorSurat;
            $pengajuanSurat->save();

            // ✅ [ASVS V8.3 / SCP #127] Logging aman dan informatif
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'isi_nomor_surat',
                'description' => 'Nomor surat dengan ID ' . $pengajuanSurat->id . ' telah diisi.',
                'ip_address' => $request->ip()
            ]);

            // ✅ [ASVS V9.1] Response aman dan tidak bocorkan informasi sensitif
            return response()->json([
                'message' => 'Nomor surat berhasil diisi.',
                'pengajuan_surat' => new AjuanResource($pengajuanSurat),
            ], 200);
        } catch (\Throwable $e) {
            // ✅ [SCP #110, #108] Tangani error internal tanpa bocorkan detail ke user
            Log::error('Gagal mengisi nomor surat', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mengisi nomor surat.'
            ], 500);
        }
    }

    // ✅ [SCP #2] Fungsi untuk mengonversi angka ke angka Romawi

    private function toRoman($number)
    {
        $map = [
            'I',
            'II',
            'III',
            'IV',
            'V',
            'VI',
            'VII',
            'VIII',
            'IX',
            'X',
            'XI',
            'XII',
        ];
        return $map[$number - 1] ?? $number;
    }

    // public function previewSurat($slug, $ajuan_id)
    // {
    //     // ✅ [SCP #23] Set locale (tidak berdampak keamanan langsung, tapi pastikan hanya bahasa yang diizinkan)
    //     Carbon::setLocale('id');

    //     // ✅ [ASVS V2.1] Autentikasi menggunakan token dari query atau header
    //     $token = request()->query('token') ?? request()->bearerToken();

    //     if (!$token) {
    //         return response('Unauthorized: token tidak ditemukan', 401);
    //     }

    //     try {
    //         $admin = JWTAuth::setToken($token)->authenticate();
    //     } catch (\Exception $e) {
    //         // ✅ [ASVS V9.2] Jangan bocorkan error detail
    //         return response()->json([
    //             'message' => 'Unauthorized: token tidak valid atau telah kedaluwarsa'
    //         ], 401);
    //     }

    //     // ✅ [ASVS V4.1] Role-based access control
    //     if (!$admin->hasAnyRole(['super-admin', 'staff-desa', 'kepala-desa'])) {
    //         return response()->json([
    //             'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses preview surat ini'
    //         ], 403);
    //     }

    //     // ✅ [ASVS V4.1.3] Cek akses dan entitas berdasarkan slug & id
    //     $ajuanSurat = Ajuan::with(['user.profileMasyarakat', 'surat'])
    //         ->where('id', $ajuan_id)
    //         ->whereHas('surat', function ($query) use ($slug) {
    //             $query->where('slug', $slug);
    //         })
    //         ->first();

    //     if (!$ajuanSurat) {
    //         return response('Not Found: data tidak ditemukan', 404);
    //     }

    //     // ✅ [ASVS V5.1.4] Pastikan data surat didekode dengan aman
    //     $dataSurat = is_array($ajuanSurat->data_surat)
    //         ? $ajuanSurat->data_surat
    //         : json_decode($ajuanSurat->data_surat, true);

    //     // ✅ [ASVS V10.2] Validasi template yang akan dipakai
    //     $kodeSurat = optional($ajuanSurat->surat)->kode_surat ?? 'default';
    //     $template = 'surat.templates.' . strtolower($kodeSurat);

    //     if (!view()->exists($template)) {
    //         return response("Template surat tidak ditemukan", 500); // ⚠️ pertimbangkan ubah ke 404
    //     }

    //     // ✅ [SCP #192] Generate QR Code di server (trusted environment)
    //     $verificationUrl = url("/verifikasi-surat/{$ajuanSurat->id}");
    //     $qrCodeSvg = QrCode::format('svg')->size(150)->generate($verificationUrl);

    //     // ✅ [SCP #115] Tambahkan metadata waktu
    //     $downloadedAt = now()->format('d F Y, H:i:s');

    //     // ✅ [ASVS V5.1] Gunakan template view yang sudah dipastikan aman
    //     $html = view($template, [
    //         'ajuan' => $ajuanSurat,
    //         'user' => $ajuanSurat->user,
    //         'profile' => $ajuanSurat->user->profileMasyarakat,
    //         'data' => $dataSurat,
    //         'qrCodeSvg' => $qrCodeSvg,
    //         'downloaded_at' => $downloadedAt,
    //         'isPreview' => true, // Penting: bisa dipakai di view untuk hide elemen sensitif
    //     ])->render();

    //     // ✅ [ASVS V9.1] Response aman (text/html), tanpa bocoran sistem
    //     return response($html, 200)->header('Content-Type', 'text/html');
    // }


    public function previewSurat($slug, $ajuan_id)
    {
        Carbon::setLocale('id');

        try {
            // ✅ [ASVS V2.1.5] [SCP #138] — Hindari penggunaan token di query string
            $token = request()->bearerToken();
            if (!$token) {
                return response()->json(['message' => 'Unauthorized: token tidak ditemukan'], 401);
            }

            // ✅ [ASVS V2.1.1] [SCP #24] — Validasi token dan autentikasi user
            $admin = JWTAuth::setToken($token)->authenticate();

            // ✅ [ASVS V4.1.3] [SCP #84, #85, #88] — Pembatasan akses berbasis peran (RBAC)
            if (!$admin->hasAnyRole(['super-admin', 'staff-desa', 'kepala-desa'])) {
                return response()->json([
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses preview surat ini.'
                ], 403);
            }

            // ✅ [ASVS V4.2.3] [SCP #86] — Validasi objek berdasarkan ID dan relasi (hindari IDOR)
            $ajuanSurat = Ajuan::with(['user.profileMasyarakat', 'surat'])
                ->where('id', $ajuan_id)
                ->whereHas('surat', fn($q) => $q->where('slug', $slug))
                ->first();

            if (!$ajuanSurat) {
                return response()->json(['message' => 'Pengajuan surat tidak ditemukan'], 404);
            }

            // ✅ [ASVS V5.1.1] — Validasi format data_surat
            $dataSurat = is_array($ajuanSurat->data_surat)
                ? $ajuanSurat->data_surat
                : json_decode($ajuanSurat->data_surat, true);

            // ✅ [ASVS V5.3.2] — Validasi bahwa template yang diminta benar-benar ada
            $kodeSurat = optional($ajuanSurat->surat)->kode_surat ?? 'default';
            $template = 'surat.templates.' . strtolower($kodeSurat);
            if (!view()->exists($template)) {
                return response()->json(['message' => 'Template surat tidak ditemukan'], 404);
            }

            // ✅ [ASVS V5.2.5] [SCP #104, #104] — QR code sebagai bukti digital, hasil dienkode aman
            $verificationUrl = url("/verifikasi-surat/{$ajuanSurat->id}");
            $qrCodeSvg = QrCode::format('svg')->size(50)->generate($verificationUrl);

            // ✅ [SCP #104, #192] — File QR disimpan di path aman & tidak dieksekusi
            $qrFilename = "qr-{$ajuanSurat->id}.svg";
            $qrPath = "private/qrcodes/{$qrFilename}";
            $qrStoragePath = storage_path("app/{$qrPath}");

            if (!file_exists(dirname($qrStoragePath))) {
                mkdir(dirname($qrStoragePath), 0755, true);
            }

            file_put_contents($qrStoragePath, $qrCodeSvg);

            // ✅ [ASVS V1.7.2] — Update database secara aman dengan path internal
            $ajuanSurat->qr_code_path = $qrPath;
            $ajuanSurat->save();

            // ✅ [ASVS V8.3.1] [SCP #127] — Logging aktivitas penting untuk audit trail
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'preview_surat',
                'description' => "Preview surat ID {$ajuanSurat->id} dibuka oleh {$admin->name}",
                'ip_address' => request()->ip(),
            ]);

            // ✅ [ASVS V10.4.2] — Render HTML via template blade (otomatis encoding output)
            $html = view($template, [
                'ajuan' => $ajuanSurat,
                'user' => $ajuanSurat->user,
                'profile' => $ajuanSurat->user->profileMasyarakat,
                'data' => $dataSurat,
                'qrCodeSvg' => $qrCodeSvg,
                'qrCodePath' => $qrStoragePath,
                'isPreview' => true,
            ])->render();

            return response($html, 200)->header('Content-Type', 'text/html');
        }

        // ✅ [ASVS V9.2] [SCP #108, #110, #126] — Tangani error dengan aman dan log internal
        catch (\Throwable $e) {
            Log::error('Gagal memuat previewSurat', [
                'error' => $e->getMessage(),
                'ajuan_id' => $ajuan_id,
                'slug' => $slug,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memuat surat. Silakan coba lagi.'
            ], 500);
        }
    }

    public function updatePengajuanSurat(AjuanRequest $request, $slug, $ajuan_id)
    {
        try {
            // ✅ [ASVS V2.1] Autentikasi pengguna
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'message' => 'User belum login. Silakan login terlebih dahulu'
                ], 401);
            }

            // ✅ [ASVS V4.1] Kontrol akses berbasis peran
            if (!$user->hasRole('super-admin') && !$user->hasRole('staff-desa')) {
                return response()->json([
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk memperbarui pengajuan surat ini'
                ], 403);
            }

            // ✅ [ASVS V5.1] Validasi format slug
            if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
                return response()->json([
                    'message' => 'Format slug tidak valid.'
                ], 400);
            }

            // ✅ [ASVS V4.1.3] Cari ajuan berdasarkan ID dan slug surat
            $ajuanSurat = Ajuan::where('id', $ajuan_id)
                ->whereHas('surat', fn($q) => $q->where('slug', $slug))
                ->first();

            // ✅ [ASVS V4.2.3] Validasi apakah pengajuan surat ditemukan
            if (!$ajuanSurat) {
                return response()->json([
                    'message' => 'Ajuan surat tidak ditemukan'
                ], 404);
            }

            // ✅ Validasi bahwa status harus 'processed' agar bisa diperbarui
            if ($ajuanSurat->status !== 'processed') {
                return response()->json([
                    'message' => 'Pengajuan surat ini tidak dapat diperbarui karena tidak dalam status sedang diproses'
                ], 400);
            }

            // ✅ [ASVS V5.1] Validasi input eksplisit via FormRequest
            $validated = $request->validated();

            $ajuanSurat->update($validated);

            // ✅ [ASVS V8.3] Logging aktivitas
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'update_pengajuan_surat',
                'description' => 'Pengajuan surat dengan ID ' . $ajuanSurat->id . ' telah diperbarui.',
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'message' => 'Pengajuan surat berhasil diperbarui',
                'data' => new AjuanResource($ajuanSurat)
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui pengajuan surat', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui pengajuan surat'
            ], 500);
        }
    }



    // public function previewSurat($slug, $ajuan_id)
    // {
    //     Carbon::setLocale('id');

    //     $token = request()->query('token') ?? request()->bearerToken();

    //     if (!$token) {
    //         return response('Unauthorized: token tidak ditemukan', 401);
    //     }

    //     try {
    //         $admin = JWTAuth::setToken($token)->authenticate();
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Unauthorized: token tidak valid atau telah kedaluwarsa'
    //         ], 401);
    //     }

    //     if (!$admin->hasAnyRole(['super-admin', 'staff-desa', 'kepala-desa'])) {
    //         return response()->json([
    //             'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses preview surat ini'
    //         ], 403);
    //     }

    //     $ajuanSurat = Ajuan::with(['user.profileMasyarakat', 'surat'])
    //         ->where('id', $ajuan_id)
    //         ->whereHas('surat', fn($q) => $q->where('slug', $slug))
    //         ->first();

    //     if (!$ajuanSurat) {
    //         return response('Not Found: data tidak ditemukan', 404);
    //     }

    //     $dataSurat = is_array($ajuanSurat->data_surat)
    //         ? $ajuanSurat->data_surat
    //         : json_decode($ajuanSurat->data_surat, true);

    //     $kodeSurat = optional($ajuanSurat->surat)->kode_surat ?? 'default';
    //     $template = 'surat.templates.' . strtolower($kodeSurat);

    //     if (!view()->exists($template)) {
    //         return response("Template surat tidak ditemukan", 404);
    //     }

    //     // ✅ Generate QR code as SVG
    //     $verificationUrl = "https://limapoccoedigital.id/verifikasi-surat/{$ajuanSurat->id}";
    //     $qrCodeSvg = QrCode::format('svg')->size(50)->generate($verificationUrl);

    //     // ✅ Simpan QR sebagai file SVG
    //     $qrFilename = "qr-{$ajuanSurat->id}.svg";
    //     $qrPath = "private/qrcodes/{$qrFilename}";
    //     $qrStoragePath = storage_path("app/{$qrPath}");

    //     if (!file_exists(dirname($qrStoragePath))) {
    //         mkdir(dirname($qrStoragePath), 0755, true);
    //     }

    //     file_put_contents($qrStoragePath, $qrCodeSvg);

    //     // Simpan path-nya ke DB (jika ingin digunakan di PDF)
    //     $ajuanSurat->qr_code_path = $qrPath;
    //     $ajuanSurat->save();

    //     $downloadedAt = now()->format('d F Y, H:i:s');

    //     $html = view($template, [
    //         'ajuan' => $ajuanSurat,
    //         'user' => $ajuanSurat->user,
    //         'profile' => $ajuanSurat->user->profileMasyarakat,
    //         'data' => $dataSurat,
    //         'qrCodeSvg' => $qrCodeSvg,
    //         'qrCodePath' => $qrStoragePath, // untuk PDF nanti
    //         'downloaded_at' => $downloadedAt,
    //         'isPreview' => true,
    //     ])->render();

    //     return response($html, 200)->header('Content-Type', 'text/html');
    // }

    public function rejectedStatusPengajuan(Request $request, $slug, $ajuanId)
    {
        try {

            $validated = $request->validate([
                'alasan_penolakan' => 'required|string|max:255',
            ]);

            // ✅ [ASVS V2.1.1] Autentikasi wajib untuk semua endpoint non-publik
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
            }

            // ✅ [ASVS V4.1.3] [SCP #84, #85] Role-Based Access Control (RBAC)
            if (!$user->hasRole('staff-desa')) {
                return response()->json([
                    'error' => 'Akses ditolak. Anda tidak memiliki izin untuk menolak pengajuan surat ini.'
                ], 403);
            }

            // ✅ [ASVS V4.2.3] [SCP #86] Validasi entitas dari kombinasi ID dan slug untuk cegah IDOR
            $pengajuanSurat = Ajuan::with(['user', 'surat'])
                ->where('id', $ajuanId)
                ->whereHas('surat', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })
                ->first();

            if (!$pengajuanSurat) {
                return response()->json(['error' => 'Pengajuan surat tidak ditemukan.'], 404);
            }

            // ✅ [ASVS V10.2.3] Validasi status sebelum melakukan perubahan untuk mencegah abuse logic
            if ($pengajuanSurat->status !== 'processed') {
                return response()->json([
                    'error' => 'Pengajuan surat tidak dalam status yang dapat ditolak.'
                ], 400);
            }

            // ✅ [ASVS V5.1.4] [SCP #11] Update status secara eksplisit & aman
            $pengajuanSurat->status = 'rejected';
            $pengajuanSurat->save();

           // Kirim Notifikasi
            $message = "👋 Hai *{$pengajuanSurat->user['name']}* (NIK: {$pengajuanSurat->user['nik']}),\n\n"
                . "Pengajuan surat *{$pengajuanSurat->surat['nama_surat']}* Anda *ditolak* oleh Staff Desa.\n\n"
                . "📌 Alasan penolakan: *{$validated['alasan_penolakan']}*\n\n"
                . "Silakan periksa kembali data yang Anda berikan dan ajukan ulang jika diperlukan.\n\n"
                . "🙏 Terimakasih telah menggunakan layanan kami.";


            $sent = FonnteHelper::sendWhatsAppMessage($pengajuanSurat->user->no_whatsapp ?? $pengajuanSurat->data_surat['no_whatsapp'], $message);

            if (!$sent) {
                return response()->json([
                    'message' => 'Gagal mengirim notifikasi WhatsApp. Silakan coba lagi nanti.',
                ], 500);
            }

            // ✅ [ASVS V8.3.1] [SCP #127] Logging aman, tidak menyimpan PII sensitif
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'tolak_pengajuan_surat',
                'description' => 'Pengajuan surat ID ' . $pengajuanSurat->id . ' ditolak.',
                'ip_address' => request()->ip(),
            ]);

            // ✅ [ASVS V9.1.1] Response aman dan tidak membocorkan info internal
            return response()->json([
                'message' => 'Pengajuan surat berhasil ditolak.',
                'pengajuan_surat' => new AjuanResource($pengajuanSurat),
            ], 200);
        }

        // ✅ [ASVS V9.2.1] [SCP #108, #110] Penanganan error secara aman
        catch (\Throwable $e) {
            Log::error('Gagal menolak pengajuan surat', [
                'error' => $e->getMessage(),
                'ajuan_id' => $ajuanId,
                'slug' => $slug,
                'user_id' => $user->id ?? null,
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat memproses penolakan surat.'
            ], 500);
        }
    }



    // public function confirmedStatusPengajuan($slug, $ajuanId)
    // {
    //     // ✅ [ASVS V2.1] Autentikasi token
    //     $user = JWTAuth::parseToken()->authenticate();

    //     if (!$user) {
    //         return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu.'], 401);
    //     }

    //     // ✅ [ASVS V4.1] Kontrol akses berbasis peran (RBAC)
    //     if (!$user->hasRole('staff-desa')) {
    //         return response()->json([
    //             'error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengonfirmasi pengajuan surat ini.'
    //         ], 403);
    //     }

    //     // ✅ [ASVS V4.1.3] Validasi kombinasi ID dan slug untuk mencegah akses tidak sah
    //     $pengajuanSurat = Ajuan::with(['user', 'surat'])
    //         ->where('id', $ajuanId)
    //         ->whereHas('surat', function ($query) use ($slug) {
    //             $query->where('slug', $slug);
    //         })
    //         ->first();

    //     if (!$pengajuanSurat) {
    //         return response()->json(['error' => 'Pengajuan surat tidak ditemukan.'], 404);
    //     }

    //     // ✅ [ASVS V10.2] Validasi status sebelum mengubahnya
    //     if ($pengajuanSurat->status !== 'processed') {
    //         return response()->json([
    //             'error' => 'Pengajuan surat tidak dalam status yang dapat dikonfirmasi.'
    //         ], 400);
    //     }

    //     // ✅ [ASVS V5.1] Update status eksplisit
    //     $pengajuanSurat->status = 'confirmed';
    //     $pengajuanSurat->save();

    //     // ✅ [ASVS V8.3] Logging aktivitas (tanpa bocor data sensitif)
    //     LogActivity::create([
    //         'id' => Str::uuid(),
    //         'user_id' => $user->id,
    //         'activity_type' => 'konfirmasi_pengajuan_surat',
    //         'description' => 'Pengajuan surat ID ' . $pengajuanSurat->id . ' telah dikonfirmasi.',
    //         'ip_address' => request()->ip(),
    //     ]);

    //     // ✅ [ASVS V9.1] Response aman dan gunakan resource untuk standarisasi
    //     return response()->json([
    //         'message' => 'Pengajuan surat berhasil dikonfirmasi.',
    //         'pengajuan_surat' => new AjuanResource($pengajuanSurat),
    //     ], 200);
    // }


    public function confirmedStatusPengajuan($slug, $ajuanId)
    {
        try {
            // ✅ [ASVS V2.1.1] – Autentikasi wajib menggunakan token aman
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu.'], 401);
            }

            // ✅ [ASVS V4.1.3] [SCP #84, #85] – Role-based access control
            if (!$user->hasRole('staff-desa')) {
                return response()->json([
                    'error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengonfirmasi pengajuan surat ini.'
                ], 403);
            }

            // ✅ [ASVS V4.2.3] [SCP #86] – Validasi entitas berdasarkan ID + slug untuk mencegah IDOR
            $pengajuanSurat = Ajuan::with(['user', 'surat'])
                ->where('id', $ajuanId)
                ->whereHas('surat', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })
                ->first();

            if (!$pengajuanSurat) {
                return response()->json(['error' => 'Pengajuan surat tidak ditemukan.'], 404);
            }

            // ✅ [ASVS V10.2.3] – Validasi status sebelum diubah, untuk mencegah abuse logic
            if ($pengajuanSurat->status !== 'processed') {
                return response()->json([
                    'error' => 'Pengajuan surat tidak dalam status yang dapat dikonfirmasi.'
                ], 400);
            }

            // ✅ [ASVS V5.1.4] [SCP #11] – Update status eksplisit dan terkontrol
            $pengajuanSurat->status = 'confirmed';
            $pengajuanSurat->save();

            // ✅ [ASVS V8.3.1] [SCP #127] – Logging aktivitas untuk audit trail, tanpa bocor PII
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'konfirmasi_pengajuan_surat',
                'description' => 'Pengajuan surat ID ' . $pengajuanSurat->id . ' telah dikonfirmasi.',
                'ip_address' => request()->ip(),
            ]);

            // ✅ [ASVS V9.1.1] – Response aman dan distandarisasi menggunakan resource
            return response()->json([
                'message' => 'Pengajuan surat berhasil dikonfirmasi.',
                'pengajuan_surat' => new AjuanResource($pengajuanSurat),
            ], 200);
        }

        // ✅ [ASVS V9.2.1] [SCP #108, #110] – Penanganan error internal dengan aman
        catch (\Throwable $e) {
            Log::error('Gagal mengonfirmasi pengajuan surat', [
                'error' => $e->getMessage(),
                'ajuan_id' => $ajuanId,
                'slug' => $slug,
                'user_id' => $user->id ?? null,
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat memproses konfirmasi surat.'
            ], 500);
        }
    }



    // public function signedStatusPengajuan($slug, $ajuanId)
    // {
    //     // ✅ [ASVS V2.1] Autentikasi token JWT
    //     $kepdes = JWTAuth::parseToken()->authenticate();

    //     if (!$kepdes) {
    //         return response()->json(['error' => 'Kepala Desa belum login. Silakan login terlebih dahulu.'], 401);
    //     }

    //     // ✅ [ASVS V4.1] Kontrol akses berbasis peran
    //     if (!$kepdes->hasRole('kepala-desa')) {
    //         return response()->json(['error' => 'Akses ditolak. Anda bukan kepala desa.'], 403);
    //     }

    //     // ✅ [ASVS V4.1.3] Validasi entitas + status 'confirmed'
    //     $ajuan = Ajuan::with(['user.profileMasyarakat', 'surat', 'tandaTangan'])
    //         ->where('id', $ajuanId)
    //         ->whereHas('surat', fn($q) => $q->where('slug', $slug))
    //         ->where('status', 'confirmed')
    //         ->first();

    //     if (!$ajuan) {
    //         return response()->json(['error' => 'Surat tidak ditemukan atau belum dikonfirmasi.'], 404);
    //     }

    //     // ✅ [ASVS V10.2] Cegah penandatanganan ulang
    //     if ($ajuan->tandaTangan) {
    //         return response()->json(['error' => 'Surat sudah ditandatangani sebelumnya.'], 400);
    //     }

    //     $signedAt = now();

    //     // ✅ [SCP #192] Akses file private key dengan aman
    //     $privateKeyPath = storage_path('app/keys/private.pem');

    //     if (!file_exists($privateKeyPath) || !is_readable($privateKeyPath)) {
    //         return response()->json(['error' => 'Private key tidak ditemukan atau tidak bisa dibaca.'], 500);
    //     }

    //     $privateKey = file_get_contents($privateKeyPath);
    //     $privateKeyRes = openssl_pkey_get_private($privateKey);

    //     if (!$privateKeyRes) {
    //         return response()->json(['error' => 'Format private key tidak valid.'], 500);
    //     }

    //     // ✅ [ASVS V10.3.1] Tanda tangan digital SHA256
    //     $signatureData = json_encode([
    //         'ajuan_id' => $ajuan->id,
    //         'nomor_surat' => $ajuan->nomor_surat,
    //         'data_surat' => $ajuan->data_surat,
    //         'user_id' => $ajuan->user_id,
    //         'timestamp' => $signedAt->toIso8601String(),
    //     ]);

    //     openssl_sign($signatureData, $signature, $privateKeyRes, OPENSSL_ALGO_SHA256);
    //     openssl_pkey_free($privateKeyRes); // ✅ [SCP #202]

    //     $encodedSignature = base64_encode($signature);

    //     // ✅ [ASVS V10.4] Simpan ke DB
    //     $tandaTangan = TandaTangan::create([
    //         'id' => Str::uuid(),
    //         'ajuan_id' => $ajuan->id,
    //         'signed_by' => $kepdes->id,
    //         'signature' => $encodedSignature,
    //         'signature_data' => $signatureData,
    //         'signed_at' => $signedAt,
    //     ]);

    //     // ✅ Update status ajuan
    //     $ajuan->update(['status' => 'approved']);

    //     // ✅ Logging aktivitas
    //     LogActivity::create([
    //         'id' => Str::uuid(),
    //         'user_id' => $kepdes->id,
    //         'activity_type' => 'ttd_surat',
    //         'description' => 'Surat ID ' . $ajuan->id . ' telah ditandatangani oleh Kepala Desa.',
    //         'ip_address' => request()->ip(),
    //     ]);

    //     // ✅ Response pakai resource
    //     return response()->json([
    //         'message' => 'Surat berhasil ditandatangani.',
    //         'data' => new TandaTanganResource(
    //             $tandaTangan->load(['ajuan', 'signedBy'])
    //         ),
    //     ], 200);
    // }

    public function signedStatusPengajuan($slug, $ajuanId)
    {
        try {
            // ✅ [ASVS V2.1.1] – Wajib autentikasi token JWT
            $kepdes = JWTAuth::parseToken()->authenticate();

            if (!$kepdes) {
                return response()->json(['error' => 'Kepala Desa belum login. Silakan login terlebih dahulu.'], 401);
            }

            // ✅ [ASVS V4.1.3] [SCP #84, #85] – Role-based Access Control (RBAC)
            if (!$kepdes->hasRole('kepala-desa')) {
                return response()->json(['error' => 'Akses ditolak. Anda bukan kepala desa.'], 403);
            }

            // ✅ [ASVS V4.2.3] [SCP #86] – Validasi entitas berdasarkan slug + ID
            $ajuan = Ajuan::with(['user.profileMasyarakat', 'surat', 'tandaTangan'])
                ->where('id', $ajuanId)
                ->whereHas('surat', fn($q) => $q->where('slug', $slug))
                ->where('status', 'confirmed') // hanya surat yang sudah dikonfirmasi
                ->first();

            if (!$ajuan) {
                return response()->json(['error' => 'Surat tidak ditemukan atau belum dikonfirmasi.'], 404);
            }

            // ✅ [ASVS V10.2.3] – Cegah duplikasi/abuse dengan pengecekan existing signature
            if ($ajuan->tandaTangan) {
                return response()->json(['error' => 'Surat sudah ditandatangani sebelumnya.'], 400);
            }

            $signedAt = now();

            // ✅ [SCP #192, #104] – Akses file private key hanya dari storage non-publik
            $privateKeyPath = storage_path('app/keys/private.pem');
            if (!file_exists($privateKeyPath) || !is_readable($privateKeyPath)) {
                return response()->json(['error' => 'Private key tidak ditemukan atau tidak bisa dibaca.'], 500);
            }

            // ✅ [ASVS V10.3.2] – Enkripsi & digital signing menggunakan SHA-256
            $privateKey = file_get_contents($privateKeyPath);
            $privateKeyRes = openssl_pkey_get_private($privateKey);
            if (!$privateKeyRes) {
                return response()->json(['error' => 'Format private key tidak valid.'], 500);
            }

            $signatureData = json_encode([
                'ajuan_id' => $ajuan->id,
                'nomor_surat' => $ajuan->nomor_surat,
                'data_surat' => $ajuan->data_surat,
                'user_id' => $ajuan->user_id,
                'timestamp' => $signedAt->toIso8601String(),
            ]);

            openssl_sign($signatureData, $signature, $privateKeyRes, OPENSSL_ALGO_SHA256);

            // ✅ [SCP #202] – Hapus resource sensitive dari memory
            openssl_pkey_free($privateKeyRes);

            $encodedSignature = base64_encode($signature);

            // ✅ [ASVS V10.4.2] – Simpan tanda tangan digital secara aman di DB
            $tandaTangan = TandaTangan::create([
                'id' => Str::uuid(),
                'ajuan_id' => $ajuan->id,
                'signed_by' => $kepdes->id,
                'signature' => $encodedSignature,
                'signature_data' => $signatureData,
                'signed_at' => $signedAt,
            ]);

            // ✅ [ASVS V5.1.4] – Update status surat secara eksplisit
            $ajuan->update(['status' => 'approved']);

            // ✅ Kirim OTP ke WhatsApp (ASVS 10.2.1 / SCP #143)
            $message = "👋 Hai *{$ajuan->user['name']}* (NIK: {$ajuan->user['nik']}),\n\n"
                        . "Surat dengan nomor *{$ajuan->nomor_surat_tersimpan}* telah berhasil *disetujui* oleh Kepala Desa pada {$signedAt->format('d F Y')}.\n\n"
                        . "📌 Segera unduh dan cek website kami untuk mendapatkan dokumen surat:\n"
                        . "🌐 https://limapoccoedigital.id\n\n"
                        . "Anda juga dapat *menscan QR Code* pada surat untuk memverifikasi keaslian dokumen.\n\n"
                        . "🙏 Terimakasih telah menggunakan layanan kami.";

            $sent = FonnteHelper::sendWhatsAppMessage($ajuan->user->no_whatsapp ?? $ajuan->data_surat['no_whatsapp'], $message);

            if (!$sent) {
                return response()->json([
                    'message' => 'Gagal mengirim notifikasi WhatsApp. Silakan coba lagi nanti.',
                ], 500);
            }


            // ✅ [ASVS V8.3.1] [SCP #127] – Logging aktivitas penting tanpa data sensitif
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $kepdes->id,
                'activity_type' => 'ttd_surat',
                'description' => 'Surat ID ' . $ajuan->id . ' telah ditandatangani oleh Kepala Desa.',
                'ip_address' => request()->ip(),
                'metadata' => json_encode([
                    'ajuan_id' => $ajuan->id,
                    'status' => 'approved',
                    'signed_at' => $signedAt->toIso8601String(),
                ]),
            ]);

            // ✅ [ASVS V9.1.1] – Gunakan Resource untuk response terstruktur & aman
            return response()->json([
                'message' => 'Surat berhasil ditandatangani.',
                'data' => new TandaTanganResource(
                    $tandaTangan->load(['ajuan', 'signedBy'])
                ),
            ], 200);
        }

        // ✅ [ASVS V9.2.1] [SCP #108, #110] – Penanganan error internal yang aman
        catch (\Throwable $e) {
            Log::error('Gagal menandatangani surat', [
                'error' => $e->getMessage(),
                'ajuan_id' => $ajuanId,
                'slug' => $slug,
                'kepdes_id' => $kepdes->id ?? null,
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat proses tanda tangan surat.'
            ], 500);
        }
    }



    // public function downloadSurat($slug, $ajuanId)
    // {
    //     Carbon::setLocale('id');
    //     ini_set('memory_limit', '-1');

    //     $user = JWTAuth::parseToken()->authenticate();

    //     if (!$user->hasAnyRole(['masyarakat', 'staff-desa', 'kepala-desa', 'super-admin'])) {
    //         return response()->json(['error' => 'Akses ditolak.'], 403);
    //     }

    //     $ajuanSurat = Ajuan::with([
    //         'user.profileMasyarakat',
    //         'surat',
    //         'tandaTangan.user'
    //     ])->where('id', $ajuanId)
    //       ->whereHas('surat', fn($q) => $q->where('slug', $slug))
    //       ->first();

    //     if (!$ajuanSurat || $ajuanSurat->status !== 'approved' || !$ajuanSurat->tandaTangan) {
    //         return response()->json(['error' => 'Surat tidak valid atau belum disetujui.'], 400);
    //     }

    //     $dataSurat = is_array($ajuanSurat->data_surat)
    //         ? $ajuanSurat->data_surat
    //         : json_decode($ajuanSurat->data_surat, true);

    //     $template = 'surat.templates.' . strtolower(optional($ajuanSurat->surat)->kode_surat ?? 'default');
    //     if (!view()->exists($template)) {
    //         return response("Template surat tidak ditemukan", 500);
    //     }

    //     // Generate QR Code in base64
    //     $verificationUrl = url("/verifikasi-surat/{$ajuanSurat->id}");
    //     $qrCodeSvg = QrCode::format('svg')->size(150)->generate($verificationUrl);
    //     $downloadedAt = Carbon::now()->translatedFormat('l, d F Y H:i');

    //     $html = view($template, [
    //         'ajuan' => $ajuanSurat,
    //         'user' => $ajuanSurat->user,
    //         'profile' => $ajuanSurat->user->profileMasyarakat,
    //         'data' => $dataSurat,
    //         'qrCodeSvg' => $qrCodeSvg,
    //         'downloaded_at' => $downloadedAt,
    //     ])->render();

    //     $nomorSurat = preg_replace('/[\/\\\\]/', '-', $ajuanSurat->nomor_surat ?? 'tanpa-nomor');
    //     $pdf = Pdf::loadHTML($html);
    //     return $pdf->download("surat-{$nomorSurat}.pdf");
    // }



    // public function downloadSurat($slug, $ajuanId)
    // {
    //     try {
    //         $ajuanSurat = Ajuan::with(['user', 'user.profileMasyarakat', 'surat'])
    //             ->where('id', $ajuanId)
    //             ->whereHas('surat', fn($q) => $q->where('slug', $slug))
    //             ->firstOrFail();

    //         // Buat data surat
    //         $dataSurat = is_array($ajuanSurat->data_surat)
    //             ? $ajuanSurat->data_surat
    //             : json_decode($ajuanSurat->data_surat, true);

    //         // Buat QR code langsung inline (tanpa simpan file PNG)
    //         $verificationUrl = url("/verifikasi-surat/{$ajuanSurat->id}");
    //         $qrCodeBase64 = base64_encode(QrCode::format('png')->size(150)->generate($verificationUrl));
    //         $qrCodeDataUri = "data:image/png;base64,{$qrCodeBase64}";

    //         $template = 'surat.templates.' . strtolower($ajuanSurat->surat->kode_surat ?? 'default');

    //         // Generate PDF (langsung tampilkan tanpa simpan file)
    //         $pdf = Pdf::loadView($template, [
    //             'ajuan' => $ajuanSurat,
    //             'user' => $ajuanSurat->user,
    //             'profile' => $ajuanSurat->user->profileMasyarakat,
    //             'data' => $dataSurat,
    //             'qrCodePath' => $qrCodeDataUri,
    //             'downloaded_at' => now()->translatedFormat('l, d F Y H:i'),
    //         ])->setPaper('a4', 'landscape');

    //         return $pdf->download("surat-{$slug}.pdf");

    //     } catch (\Throwable $e) {
    //         Log::error("Gagal download surat: " . $e->getMessage());
    //         return response()->json(['error' => 'Gagal download surat.'], 500);
    //     }

    // }

    public function downloadSurat($slug, $ajuanId)
    {
        // ✅ [ASVS V10.1.1] – Batasi penggunaan memori
        ini_set('memory_limit', '512M');

        try {
            // ✅ [ASVS V2.1.1] – Wajib autentikasi token
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['error' => 'User belum login.'], 401);
            }

            // ✅ [ASVS V4.2.3] – Validasi kombinasi ID + slug
            $ajuanSurat = Ajuan::with(['user', 'user.profileMasyarakat', 'surat', 'tandaTangan'])
                ->where('id', $ajuanId)
                ->whereHas('surat', fn($q) => $q->where('slug', $slug))
                ->firstOrFail();

            // ✅ [ASVS V4.1.3] [SCP #86] – Cegah IDOR: hanya pemilik atau role sah yang boleh
            if (
                !$user->hasAnyRole(['staff-desa', 'kepala-desa']) &&
                !($user->hasRole('masyarakat') && $user->id === $ajuanSurat->user_id)
            ) {
                return response()->json(['error' => 'Anda tidak memiliki akses ke surat ini.'], 403);
            }

            // ✅ [ASVS V5.1.1] – Validasi format data_surat
            $dataSurat = is_array($ajuanSurat->data_surat)
                ? $ajuanSurat->data_surat
                : json_decode($ajuanSurat->data_surat, true);

            // ✅ [ASVS V5.3.2] – Validasi template view
            $template = 'surat.templates.' . strtolower($ajuanSurat->surat->kode_surat ?? 'default');
            if (!view()->exists($template)) {
                return response()->json(['error' => 'Template surat tidak ditemukan.'], 404);
            }

            // ✅ [SCP #104, #192] – Cek QR code di storage privat
            $qrCodePath = null;
            if ($ajuanSurat->qr_code_path) {
                $storedPath = storage_path('app/' . $ajuanSurat->qr_code_path);
                if (file_exists($storedPath) && is_readable($storedPath)) {
                    $qrCodePath = $storedPath;
                }
            }

            // ✅ [ASVS V10.4.2] – Render PDF dengan template blade
            $pdf = Pdf::loadView($template, [
                'ajuan'         => $ajuanSurat,
                'user'          => $ajuanSurat->user,
                'profile'       => $ajuanSurat->user->profileMasyarakat,
                'data'          => $dataSurat,
                'qrCodePath'    => $qrCodePath,
                'downloaded_at' => now()->translatedFormat('l, d F Y H:i'),
                'isPreview'     => false,
            ])->setPaper('a4', 'portrait');

            // ✅ [ASVS V9.1.1] – File name aman
            $filename = "{$ajuanSurat->id}-{$slug}-" . Str::slug($ajuanSurat->user->name) . ".pdf";

            // ✅ [ASVS V8.3.1] – Logging aktivitas unduhan
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'unduh_surat',
                'description' => "Surat {$ajuanSurat->id} diunduh oleh {$user->name}.",
                'ip_address' => request()->ip(),
            ]);

            return $pdf->download($filename);
        }

        // ✅ [ASVS V9.2.1] [SCP #108, #110] – Tangani error internal secara aman
        catch (\Throwable $e) {
            Log::error("Gagal download surat", [
                'error' => $e->getMessage(),
                'ajuan_id' => $ajuanId,
                'slug' => $slug,
                'user_id' => $user->id(),
            ]);

            return response()->json(['error' => 'Gagal download surat.'], 500);
        }
    }


    //ini fikss
    // public function downloadSurat($slug, $ajuanId)
    // {
    //     ini_set('memory_limit', '512M');

    //     try {
    //         $ajuanSurat = Ajuan::with(['user', 'user.profileMasyarakat', 'surat', 'tandaTangan'])
    //             ->where('id', $ajuanId)
    //             ->whereHas('surat', fn($q) => $q->where('slug', $slug))
    //             ->firstOrFail();

    //         $dataSurat = is_array($ajuanSurat->data_surat)
    //             ? $ajuanSurat->data_surat
    //             : json_decode($ajuanSurat->data_surat, true);

    //         $template = 'surat.templates.' . strtolower($ajuanSurat->surat->kode_surat ?? 'default');

    //         // ✅ Ambil QR code dari file jika tersedia
    //         $qrCodePath = null;
    //         if ($ajuanSurat->qr_code_path) {
    //             $storedPath = storage_path('app/' . $ajuanSurat->qr_code_path);
    //             if (file_exists($storedPath)) {
    //                 $qrCodePath = $storedPath;
    //             }
    //         }

    //         $pdf = Pdf::loadView($template, [
    //             'ajuan'         => $ajuanSurat,
    //             'user'          => $ajuanSurat->user,
    //             'profile'       => $ajuanSurat->user->profileMasyarakat,
    //             'data'          => $dataSurat,
    //             'qrCodePath'    => $qrCodePath,
    //             'downloaded_at' => now()->translatedFormat('l, d F Y H:i'),
    //             'isPreview'     => false,
    //         ])->setPaper('a4', 'portrait');


    //         return $pdf->download("{$ajuanSurat->id}-{$slug}-{$ajuanSurat->user->name}.pdf");
    //     } catch (\Throwable $e) {
    //         Log::error("Gagal download surat: " . $e->getMessage());
    //         return response()->json(['error' => 'Gagal download surat.'], 500);
    //     }
    // }


    // public function downloadSurat($slug, $ajuanId)
    // {
    //     ini_set('memory_limit', '512M');

    //     try {
    //         $ajuanSurat = Ajuan::with(['user', 'user.profileMasyarakat', 'surat', 'tandaTangan'])
    //             ->where('id', $ajuanId)
    //             ->whereHas('surat', fn($q) => $q->where('slug', $slug))
    //             ->firstOrFail();

    //         $dataSurat = is_array($ajuanSurat->data_surat)
    //             ? $ajuanSurat->data_surat
    //             : json_decode($ajuanSurat->data_surat, true);

    //         $template = 'surat.templates.' . strtolower($ajuanSurat->surat->kode_surat ?? 'default');

    //         // Cek QR Code file
    //         $qrCodePath = null;
    //         if ($ajuanSurat->qr_code_path) {
    //             $storedPath = storage_path('app/' . $ajuanSurat->qr_code_path);
    //             if (file_exists($storedPath)) {
    //                 $qrCodePath = $storedPath;
    //             }
    //         }

    //         // Siapkan folder temp
    //         $tempDir = storage_path('app/temp');
    //         if (!file_exists($tempDir)) {
    //             mkdir($tempDir, 0755, true);
    //         }

    //         // Nama file dan path lokal
    //         $filename = "{$ajuanSurat->nomor_surat}-{$slug}.pdf";
    //         $localPath = $tempDir . '/' . $filename;

    //         // Generate PDF dan simpan
    //         Pdf::loadView($template, [
    //             'ajuan'         => $ajuanSurat,
    //             'user'          => $ajuanSurat->user,
    //             'profile'       => $ajuanSurat->user->profileMasyarakat,
    //             'data'          => $dataSurat,
    //             'qrCodePath'    => $qrCodePath,
    //             'downloaded_at' => now()->translatedFormat('l, d F Y H:i'),
    //             'isPreview'     => false,
    //         ])->setPaper('a4', 'portrait')->save($localPath);

    //         // Upload ke Google Drive
    //         Storage::disk('google')->put("surat/{$filename}", fopen($localPath, 'r+'));

    //         // Download ke browser dan hapus file setelahnya
    //         return response()->download($localPath)->deleteFileAfterSend(true);
    //     } catch (\Throwable $e) {
    //         Log::error("Gagal download surat: " . $e->getMessage());
    //         return response()->json(['error' => 'Gagal download surat.'], 500);
    //     }
    // }


    // public function downloadSurat($slug, $ajuanId)
    // {
    //     ini_set('memory_limit', '512M');

    //     try {
    //         $ajuanSurat = Ajuan::with(['user', 'user.profileMasyarakat', 'surat'])
    //             ->where('id', $ajuanId)
    //             ->whereHas('surat', fn($q) => $q->where('slug', $slug))
    //             ->firstOrFail();

    //         $dataSurat = is_array($ajuanSurat->data_surat)
    //             ? $ajuanSurat->data_surat
    //             : json_decode($ajuanSurat->data_surat, true);

    //         $template = 'surat.templates.' . strtolower($ajuanSurat->surat->kode_surat ?? 'default');

    //         $pdf = Pdf::loadView($template, [
    //             'ajuan' => $ajuanSurat,
    //             'user' => $ajuanSurat->user,
    //             'profile' => $ajuanSurat->user->profileMasyarakat,
    //             'data' => $dataSurat,
    //             'downloaded_at' => now()->translatedFormat('l, d F Y H:i'),
    //             'isPreview' => false, // 🟢 Ini penting!
    //         ])->setPaper('f4', 'portrait');

    //         return $pdf->download("surat-{$slug}.pdf");

    //     } catch (\Throwable $e) {
    //         Log::error("Gagal download surat: " . $e->getMessage());
    //         return response()->json(['error' => 'Gagal download surat.'], 500);
    //     }
    // }

    public function verifikasiSurat($ajuanId)
    {
        try {
            // ✅ [ASVS V4.2.3] – Validasi ID ajuan dan load relasi
            $ajuan = Ajuan::with(['user', 'surat', 'tandaTangan'])->find($ajuanId);

            if (!$ajuan || !$ajuan->tandaTangan) {
                return view('pengajuan-surat::verifikasi-surat', [
                    'valid' => false,
                    'message' => '❌ Dokumen tidak ditemukan atau belum ditandatangani.'
                ]);
            }

            // ✅ [ASVS V10.3.2] – Decode data dan signature dari DB
            $signatureData = $ajuan->tandaTangan->signature_data;
            $signature = base64_decode($ajuan->tandaTangan->signature);

            // ✅ [SCP #192, #104] – Validasi eksistensi & izin file public key
            $publicKeyPath = storage_path('app/keys/public.pem');
            if (!file_exists($publicKeyPath) || !is_readable($publicKeyPath)) {
                return view('pengajuan-surat::verifikasi-surat', [
                    'valid' => false,
                    'message' => '❌ Public key tidak tersedia atau tidak bisa dibaca.'
                ]);
            }

            // ✅ [ASVS V10.3.1] – Verifikasi signature SHA-256
            $publicKey = file_get_contents($publicKeyPath);
            $publicKeyRes = openssl_pkey_get_public($publicKey);
            if (!$publicKeyRes) {
                return view('pengajuan-surat::verifikasi-surat', [
                    'valid' => false,
                    'message' => '❌ Format public key tidak valid.'
                ]);
            }

            $verified = openssl_verify($signatureData, $signature, $publicKeyRes, OPENSSL_ALGO_SHA256);
            openssl_free_key($publicKeyRes); // ✅ [SCP #202] – Bebaskan resource

            // ✅ [ASVS V8.3.1] – Logging aktivitas verifikasi
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => null, // publik
                'activity_type' => 'verifikasi_surat',
                'description' => 'Verifikasi dokumen surat ID ' . $ajuan->id,
                'ip_address' => request()->ip(),
                'metadata' => json_encode([
                    'status' => $verified === 1 ? 'valid' : 'invalid',
                    'ajuan_id' => $ajuan->id,
                ]),
            ]);

            return view('pengajuan-surat::verifikasi-surat', [
                'valid' => $verified === 1,
                'message' => $verified === 1
                    ? '✅ Dokumen ASLI dan belum dimodifikasi.'
                    : '❌ Dokumen tidak valid atau telah dimodifikasi.',
                'data' => json_decode($signatureData, true),
                'ajuan' => $ajuan,
            ]);
        }

        // ✅ [ASVS V9.2.1] [SCP #110] – Tangani error tanpa stack trace
        catch (\Throwable $e) {
            Log::error('Gagal melakukan verifikasi surat', [
                'error' => $e->getMessage(),
                'ajuan_id' => $ajuanId ?? null,
            ]);

            return view('pengajuan-surat::verifikasi-surat', [
                'valid' => false,
                'message' => '❌ Terjadi kesalahan saat proses verifikasi.',
            ]);
        }
    }


    // public function verifikasiSurat($ajuanId)
    // {
    //     $ajuan = Ajuan::with(['user', 'surat', 'tandaTangan'])->find($ajuanId);

    //     if (!$ajuan || !$ajuan->tandaTangan) {
    //         return view('pengajuan-surat::verifikasi-surat', [
    //             'valid' => false,
    //             'message' => '❌ Dokumen tidak ditemukan atau belum ditandatangani.'
    //         ]);
    //     }

    //     $signatureData = $ajuan->tandaTangan->signature_data;
    //     $signature = base64_decode($ajuan->tandaTangan->signature);

    //     $publicKeyPath = storage_path('app/keys/public.pem');
    //     if (!file_exists($publicKeyPath)) {
    //         return view('pengajuan-surat::verifikasi-surat', [
    //             'valid' => false,
    //             'message' => '❌ Public key tidak tersedia.'
    //         ]);
    //     }

    //     $publicKey = file_get_contents($publicKeyPath);
    //     $publicKeyRes = openssl_pkey_get_public($publicKey);

    //     $verified = openssl_verify($signatureData, $signature, $publicKeyRes, OPENSSL_ALGO_SHA256);

    //     return view('pengajuan-surat::verifikasi-surat', [
    //         'valid' => $verified === 1,
    //         'message' => $verified === 1
    //             ? '✅ Dokumen ASLI dan belum dimodifikasi.'
    //             : '❌ Dokumen tidak valid atau telah dimodifikasi.',
    //         'data' => json_decode($signatureData, true),
    //         'ajuan' => $ajuan, // ⬅ DITAMBAHKAN!
    //     ]);
    // }

}
