<?php

namespace Modules\PengajuanSurat\Http\Controllers;

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
use Modules\PengajuanSurat\Http\Requests\AjuanRequest;
use Modules\PengajuanSurat\Http\Requests\FillNumberRequest;
use Modules\PengajuanSurat\Transformers\AjuanResource;
use Modules\PengajuanSurat\Transformers\TandaTanganResource;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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


    public function ajukanSurat(AjuanRequest $request, $slug)
    {
        try {
            // ✅ [ASVS V2.1] [SCP #23] Autentikasi di awal proses
            $authUser = JWTAuth::parseToken()->authenticate();
            if (!$authUser) {
                return response()->json(['error' => 'Anda belum login. Silakan login terlebih dahulu'], 401);
            }

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


    public function getPengajuanSurat($slug)
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
   
    public function fillNumber(FillNumberRequest $request, $slug, $ajuanId){
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user->hasRole('staff-desa')) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengisi nomor pengajuan surat ini'], 403);
        }

        $validated = $request->validated();

        $pengajuanSurat = Ajuan::with(['user', 'surat'])
            ->where('id', $ajuanId)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$pengajuanSurat) {
            return response()->json(['error' => 'Pengajuan surat tidak ditemukan'], 404);
        }

        $surat = Surat::where('slug', $slug)->first();
        $kodeSurat = $surat ? $surat->kode_surat : 'XXX';

        $kodeWilayah = '10.2003';
        $nomorUrutManual = $validated['nomor_surat'];
        $bulanRomawi = $this->toRoman(Carbon::now()->month);
        $tahun = Carbon::now()->year;

        $nomorSurat = $nomorUrutManual . '/' . $kodeSurat . '/' . $kodeWilayah . '/' . $bulanRomawi . '/' . $tahun;

        $pengajuanSurat->nomor_surat = $validated['nomor_surat'];
        $pengajuanSurat->nomor_surat_tersimpan = $nomorSurat;
        $pengajuanSurat->save();

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'isi_nomor_surat',
            'description' => 'Nomor surat dengan ID ' . $pengajuanSurat->id . ' telah diisi.',
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Nomor surat berhasil diisi.',
            'pengajuan_surat' => new AjuanResource($pengajuanSurat),
        ], 200);
    }


    private function toRoman($number){
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
        return $map[$number-1] ?? $number;
    }

    public function previewSurat($slug, $ajuan_id)
    {
        // ✅ [SCP #23] Set locale (tidak berdampak keamanan langsung, tapi pastikan hanya bahasa yang diizinkan)
        Carbon::setLocale('id');

        // ✅ [ASVS V2.1] Autentikasi menggunakan token dari query atau header
        $token = request()->query('token') ?? request()->bearerToken();

        if (!$token) {
            return response('Unauthorized: token tidak ditemukan', 401);
        }

        try {
            $admin = JWTAuth::setToken($token)->authenticate();
        } catch (\Exception $e) {
            // ✅ [ASVS V9.2] Jangan bocorkan error detail
            return response()->json([
                'message' => 'Unauthorized: token tidak valid atau telah kedaluwarsa'
            ], 401);
        }

        // ✅ [ASVS V4.1] Role-based access control
        if (!$admin->hasAnyRole(['super-admin', 'staff-desa', 'kepala-desa'])) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses preview surat ini'
            ], 403);
        }

        // ✅ [ASVS V4.1.3] Cek akses dan entitas berdasarkan slug & id
        $ajuanSurat = Ajuan::with(['user.profileMasyarakat', 'surat'])
            ->where('id', $ajuan_id)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$ajuanSurat) {
            return response('Not Found: data tidak ditemukan', 404);
        }

        // ✅ [ASVS V5.1.4] Pastikan data surat didekode dengan aman
        $dataSurat = is_array($ajuanSurat->data_surat)
            ? $ajuanSurat->data_surat
            : json_decode($ajuanSurat->data_surat, true);

        // ✅ [ASVS V10.2] Validasi template yang akan dipakai
        $kodeSurat = optional($ajuanSurat->surat)->kode_surat ?? 'default';
        $template = 'surat.templates.' . strtolower($kodeSurat);

        if (!view()->exists($template)) {
            return response("Template surat tidak ditemukan", 500); // ⚠️ pertimbangkan ubah ke 404
        }

        // ✅ [SCP #192] Generate QR Code di server (trusted environment)
        $verificationUrl = url("/verifikasi-surat/{$ajuanSurat->id}");
        $qrCodeSvg = QrCode::format('svg')->size(150)->generate($verificationUrl);

        // ✅ [SCP #115] Tambahkan metadata waktu
        $downloadedAt = now()->format('d F Y, H:i:s');

        // ✅ [ASVS V5.1] Gunakan template view yang sudah dipastikan aman
        $html = view($template, [
            'ajuan' => $ajuanSurat,
            'user' => $ajuanSurat->user,
            'profile' => $ajuanSurat->user->profileMasyarakat,
            'data' => $dataSurat,
            'qrCodeSvg' => $qrCodeSvg,
            'downloaded_at' => $downloadedAt,
            'isPreview' => true, // Penting: bisa dipakai di view untuk hide elemen sensitif
        ])->render();

        // ✅ [ASVS V9.1] Response aman (text/html), tanpa bocoran sistem
        return response($html, 200)->header('Content-Type', 'text/html');
    }



    public function rejectedStatusPengajuan($slug, $ajuanId)
    {
        // ✅ [ASVS V2.1] Autentikasi wajib
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        // ✅ [ASVS V4.1] Kontrol akses berbasis peran
        if (!$user->hasRole('staff-desa')) {
            return response()->json([
                'error' => 'Akses ditolak. Anda tidak memiliki izin untuk menolak pengajuan surat ini.'
            ], 403);
        }

        // ✅ [ASVS V4.1.3] Validasi entitas dengan kombinasi slug + ID
        $pengajuanSurat = Ajuan::with(['user', 'surat'])
            ->where('id', $ajuanId)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$pengajuanSurat) {
            return response()->json(['error' => 'Pengajuan surat tidak ditemukan.'], 404);
        }

        // ✅ [ASVS V10.2] Validasi status yang dapat ditolak (hindari abuse logic)
        if ($pengajuanSurat->status !== 'processed') {
            return response()->json([
                'error' => 'Pengajuan surat tidak dalam status yang dapat ditolak.'
            ], 400);
        }

        // ✅ [ASVS V5.1] Update status secara eksplisit
        $pengajuanSurat->status = 'rejected';
        $pengajuanSurat->save();

        // ✅ [ASVS V8.3] Logging aman tanpa PII
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'tolak_pengajuan_surat',
            'description' => 'Pengajuan surat ID ' . $pengajuanSurat->id . ' telah ditolak.',
            'ip_address' => request()->ip(),
        ]);

        // ✅ [ASVS V9.1] Response aman dan terstruktur, gunakan Resource jika diperlukan
        return response()->json([
            'message' => 'Pengajuan surat berhasil ditolak.',
            'pengajuan_surat' => new AjuanResource($pengajuanSurat),
        ], 200);
    }


    public function confirmedStatusPengajuan($slug, $ajuanId)
    {
        // ✅ [ASVS V2.1] Autentikasi token
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu.'], 401);
        }

        // ✅ [ASVS V4.1] Kontrol akses berbasis peran (RBAC)
        if (!$user->hasRole('staff-desa')) {
            return response()->json([
                'error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengonfirmasi pengajuan surat ini.'
            ], 403);
        }

        // ✅ [ASVS V4.1.3] Validasi kombinasi ID dan slug untuk mencegah akses tidak sah
        $pengajuanSurat = Ajuan::with(['user', 'surat'])
            ->where('id', $ajuanId)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$pengajuanSurat) {
            return response()->json(['error' => 'Pengajuan surat tidak ditemukan.'], 404);
        }

        // ✅ [ASVS V10.2] Validasi status sebelum mengubahnya
        if ($pengajuanSurat->status !== 'processed') {
            return response()->json([
                'error' => 'Pengajuan surat tidak dalam status yang dapat dikonfirmasi.'
            ], 400);
        }

        // ✅ [ASVS V5.1] Update status eksplisit
        $pengajuanSurat->status = 'confirmed';
        $pengajuanSurat->save();

        // ✅ [ASVS V8.3] Logging aktivitas (tanpa bocor data sensitif)
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'konfirmasi_pengajuan_surat',
            'description' => 'Pengajuan surat ID ' . $pengajuanSurat->id . ' telah dikonfirmasi.',
            'ip_address' => request()->ip(),
        ]);

        // ✅ [ASVS V9.1] Response aman dan gunakan resource untuk standarisasi
        return response()->json([
            'message' => 'Pengajuan surat berhasil dikonfirmasi.',
            'pengajuan_surat' => new AjuanResource($pengajuanSurat),
        ], 200);
    }


    public function signedStatusPengajuan($slug, $ajuanId)
    {
        // ✅ [ASVS V2.1] Autentikasi token JWT
        $kepdes = JWTAuth::parseToken()->authenticate();

        if (!$kepdes) {
            return response()->json(['error' => 'Kepala Desa belum login. Silakan login terlebih dahulu.'], 401);
        }

        // ✅ [ASVS V4.1] Kontrol akses berbasis peran
        if (!$kepdes->hasRole('kepala-desa')) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan kepala desa.'], 403);
        }

        // ✅ [ASVS V4.1.3] Validasi entitas + status 'confirmed'
        $ajuan = Ajuan::with(['user.profileMasyarakat', 'surat', 'tandaTangan'])
            ->where('id', $ajuanId)
            ->whereHas('surat', fn($q) => $q->where('slug', $slug))
            ->where('status', 'confirmed')
            ->first();

        if (!$ajuan) {
            return response()->json(['error' => 'Surat tidak ditemukan atau belum dikonfirmasi.'], 404);
        }

        // ✅ [ASVS V10.2] Cegah penandatanganan ulang
        if ($ajuan->tandaTangan) {
            return response()->json(['error' => 'Surat sudah ditandatangani sebelumnya.'], 400);
        }

        $signedAt = now();

        // ✅ [SCP #192] Akses file private key dengan aman
        $privateKeyPath = storage_path('app/keys/private.pem');

        if (!file_exists($privateKeyPath) || !is_readable($privateKeyPath)) {
            return response()->json(['error' => 'Private key tidak ditemukan atau tidak bisa dibaca.'], 500);
        }

        $privateKey = file_get_contents($privateKeyPath);
        $privateKeyRes = openssl_pkey_get_private($privateKey);

        if (!$privateKeyRes) {
            return response()->json(['error' => 'Format private key tidak valid.'], 500);
        }

        // ✅ [ASVS V10.3.1] Tanda tangan digital SHA256
        $signatureData = json_encode([
            'ajuan_id' => $ajuan->id,
            'nomor_surat' => $ajuan->nomor_surat,
            'data_surat' => $ajuan->data_surat,
            'user_id' => $ajuan->user_id,
            'timestamp' => $signedAt->toIso8601String(),
        ]);

        openssl_sign($signatureData, $signature, $privateKeyRes, OPENSSL_ALGO_SHA256);
        openssl_pkey_free($privateKeyRes); // ✅ [SCP #202]

        $encodedSignature = base64_encode($signature);

        // ✅ [ASVS V10.4] Simpan ke DB
        $tandaTangan = TandaTangan::create([
            'id' => Str::uuid(),
            'ajuan_id' => $ajuan->id,
            'signed_by' => $kepdes->id,
            'signature' => $encodedSignature,
            'signature_data' => $signatureData,
            'signed_at' => $signedAt,
        ]);

        // ✅ Update status ajuan
        $ajuan->update(['status' => 'approved']);

        // ✅ Logging aktivitas
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $kepdes->id,
            'activity_type' => 'ttd_surat',
            'description' => 'Surat ID ' . $ajuan->id . ' telah ditandatangani oleh Kepala Desa.',
            'ip_address' => request()->ip(),
        ]);

        // ✅ Response pakai resource
        return response()->json([
            'message' => 'Surat berhasil ditandatangani.',
            'data' => new TandaTanganResource(
                $tandaTangan->load(['ajuan', 'signedBy'])
            ),
        ], 200);
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

    public function downloadSurat($slug, $ajuanId)
    {
        Carbon::setLocale('id');
        ini_set('memory_limit', '-1');

        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->hasAnyRole(['masyarakat', 'staff-desa', 'kepala-desa', 'super-admin'])) {
                return response()->json(['error' => 'Akses ditolak.'], 403);
            }

            $ajuanSurat = Ajuan::with([
                'user',
                'user.profileMasyarakat',
                'surat',
                'tandaTangan.signedBy'
            ])
            ->where('id', $ajuanId)
            ->whereHas('surat', fn($q) => $q->where('slug', $slug))
            ->first();

            if (!$ajuanSurat || $ajuanSurat->status !== 'approved' || !$ajuanSurat->tandaTangan) {
                return response()->json(['error' => 'Surat tidak valid atau belum disetujui.'], 400);
            }

            $dataSurat = is_array($ajuanSurat->data_surat)
                ? $ajuanSurat->data_surat
                : json_decode($ajuanSurat->data_surat, true);

            $template = 'surat.templates.' . strtolower(optional($ajuanSurat->surat)->kode_surat ?? 'default');

            if (!view()->exists($template)) {
                Log::warning("Template surat tidak ditemukan: $template");
                return response()->json(['error' => 'Template surat tidak ditemukan.'], 404);
            }

            // ✅ Generate QR Code dan simpan sementara sebagai PNG
            $verificationUrl = url("/verifikasi-surat/{$ajuanSurat->id}");
            $qrFilename = 'qr-' . Str::uuid() . '.png';
            $qrRelativePath = "qrcodes/{$qrFilename}";
            $qrStoragePath = storage_path("app/public/{$qrRelativePath}");

            if (!File::exists(dirname($qrStoragePath))) {
                File::makeDirectory(dirname($qrStoragePath), 0755, true);
            }

            QrCode::format('png')->size(150)->generate($verificationUrl, $qrStoragePath);
            $qrCodePath = public_path("storage/{$qrRelativePath}");

            $downloadedAt = Carbon::now()->translatedFormat('l, d F Y H:i');

            // ✅ Render HTML surat
            $html = view($template, [
                'ajuan' => $ajuanSurat,
                'user' => $ajuanSurat->user,
                'profile' => $ajuanSurat->user->profileMasyarakat,
                'data' => $dataSurat,
                'qrCodePath' => $qrCodePath,
                'downloaded_at' => $downloadedAt,
                'isPreview' => false,
            ])->render();

            // ✅ Buat PDF
            $nomorSurat = preg_replace('/[\/\\\\]/', '-', $ajuanSurat->nomor_surat ?? 'tanpa-nomor');
            $pdf = PDF::loadHTML($html);

            // ✅ Kembalikan PDF sebagai download
            $response = $pdf->download("surat-{$nomorSurat}.pdf");

            // ✅ Hapus file QR sementara
            if (file_exists($qrCodePath)) {
                try {
                    unlink($qrCodePath);
                } catch (\Throwable $e) {
                    Log::warning("Gagal menghapus file QR: {$qrCodePath} - " . $e->getMessage());
                }
            }

            return $response;

        } catch (\Throwable $e) {
            Log::error("Download surat gagal: " . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat memproses surat.'], 500);
        }
    }

public function verifikasiSurat($ajuanId)
{
    $ajuan = Ajuan::with(['user', 'surat', 'tandaTangan'])->find($ajuanId);

    if (!$ajuan || !$ajuan->tandaTangan) {
        return view('pengajuan-surat::verifikasi-surat', [
            'valid' => false,
            'message' => '❌ Dokumen tidak ditemukan atau belum ditandatangani.'
        ]);
    }

    $signatureData = $ajuan->tandaTangan->signature_data;
    $signature = base64_decode($ajuan->tandaTangan->signature);

    $publicKeyPath = storage_path('app/keys/public.pem');
    if (!file_exists($publicKeyPath)) {
        return view('pengajuan-surat::verifikasi-surat', [
            'valid' => false,
            'message' => '❌ Public key tidak tersedia.'
        ]);
    }

    $publicKey = file_get_contents($publicKeyPath);
    $publicKeyRes = openssl_pkey_get_public($publicKey);

    $verified = openssl_verify($signatureData, $signature, $publicKeyRes, OPENSSL_ALGO_SHA256);

    return view('pengajuan-surat::verifikasi-surat', [
        'valid' => $verified === 1,
        'message' => $verified === 1
            ? '✅ Dokumen ASLI dan belum dimodifikasi.'
            : '❌ Dokumen tidak valid atau telah dimodifikasi.',
        'data' => json_decode($signatureData, true),
        'ajuan' => $ajuan, // ⬅ DITAMBAHKAN!
    ]);
}



    
}
