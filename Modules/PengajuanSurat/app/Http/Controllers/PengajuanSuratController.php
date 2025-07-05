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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Modules\Auth\Models\AuthUser;
use Modules\PengajuanSurat\Models\TandaTangan;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;




class PengajuanSuratController extends Controller
{
    
    public function ajukanSurat(Request $request, $slug)
    {
        $authUser = JWTAuth::parseToken()->authenticate();
        if (!$authUser) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$authUser->hasRole('masyarakat') && !$authUser->hasRole('staff-desa')) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengajukan surat'], 403);
        }

        if ($authUser->hasRole('masyarakat') && !$authUser->is_profile_complete) {
            return response()->json([
                'error' => 'Profil Anda belum lengkap. Harap lengkapi profil terlebih dahulu sebelum mengajukan surat.'
            ], 422);
        }

        $surat = Surat::where('slug', $slug)->first();
        if (!$surat) {
            return response()->json(['error' => 'Surat tidak ditemukan'], 404);
        }

        $validatedData = $request->validate([
            'data_surat' => 'required|array',
            'lampiran' => 'nullable|array',
            'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $ajuan = Ajuan::create([
            'user_id' => $authUser->id, // pencatat pengaju, bukan yang diajukan
            'surat_id' => $surat->id,
            'data_surat' => json_encode($validatedData['data_surat']),
            'status' => 'processed',
        ]);

        // Simpan lampiran jika ada
        if (isset($validatedData['lampiran'])) {
            foreach ($validatedData['lampiran'] as $file) {
                $path = $file->store('lampiran', 'public');
                $ajuan->lampiran()->create([
                    'file_path' => $path,
                ]);
            }
        }

        // Ambil nama dari data_surat untuk keperluan log
        $namaPemohon = $validatedData['data_surat']['nama_lengkap'] ?? 'tidak diketahui';

        // Log aktivitas
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $authUser->id,
            'activity_type' => 'ajuan_surat',
            'description' => 'Surat "' . $surat->nama_surat . '" diajukan untuk ' . $namaPemohon,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Surat berhasil diajukan.',
            'ajuan_surat' => $ajuan,
        ], 200);
    }


    public function getPengajuanSurat($slug)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        $baseQuery = Ajuan::with([
            'user',
            'user.profileMasyarakat',
            'surat'
        ])->whereHas('surat', function ($query) use ($slug) {
            $query->where('slug', $slug);
        });

        if ($user->hasRole('masyarakat')) {
            $pengajuanSurat = $baseQuery->where('user_id', $user->id)->get();
        } elseif ($user->hasRole('staff-desa')) {
            $pengajuanSurat = $baseQuery->get();
        } elseif ($user->hasRole('kepala-desa')) {
            $pengajuanSurat = $baseQuery
                ->whereIn('status', ['confirmed', 'approved'])
                ->get();
        } else {
            return response()->json([
                'error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses pengajuan surat ini'
            ], 403);
        }

        if ($pengajuanSurat->isEmpty()) {
            return response()->json(['message' => 'Tidak ada pengajuan surat yang ditemukan'], 200);
        }

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'get_pengajuan_surat',
            'description' => 'Daftar pengajuan surat dengan slug ' . $slug . ' telah diakses.',
            'ip_address' => request()->ip(),
            'created_at' => now(), // tambahkan ini jika kolom `created_at` tidak auto
        ]);

        return response()->json([
            'message' => 'Berhasil mendapatkan daftar pengajuan surat.',
            'pengajuan_surat' => $pengajuanSurat,
        ], 200);
    }



    public function getDetailPengajuanSurat($slug, $ajuanId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('masyarakat') && !$user->hasAnyRole(['staff-desa', 'admin'])) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses detail pengajuan surat ini'], 403);
        }

        $pengajuanSurat = Ajuan::with(['user', 'user.profileMasyarakat', 'surat'])
            ->where('id', $ajuanId)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$pengajuanSurat) {
            return response()->json(['error' => 'Pengajuan surat tidak ditemukan'], 404);
        }

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'detail_pengajuan_surat',
            'description' => 'Detail pengajuan surat dengan ID ' . $pengajuanSurat->id . ' telah diakses.',
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Berhasil mendapatkan detail pengajuan surat.',
            'pengajuan_surat' => $pengajuanSurat,
        ], 200);
    }


   
    public function fillNumber(Request $request, $slug, $ajuanId){
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user->hasRole('staff-desa')) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengisi nomor pengajuan surat ini'], 403);
        }

        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:255',
        ]);

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

        $pengajuanSurat->nomor_surat = $nomorSurat;
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
            'pengajuan_surat' => $pengajuanSurat,
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
        $token = request()->query('token') ?? request()->bearerToken();

        if (!$token) {
            return response('Unauthorized: token tidak ditemukan', 401);
        }

        try {
            $admin = JWTAuth::setToken($token)->authenticate();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unauthorized: token tidak valid atau telah kedaluwarsa'
            ], 401);
        }

        if (!$admin->hasAnyRole(['super-admin', 'staff-desa', 'kepala-desa'])) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses preview surat ini'
            ], 403);
        }

        $ajuanSurat = Ajuan::with(['user.profileMasyarakat', 'surat'])
            ->where('id', $ajuan_id)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$ajuanSurat) {
            return response('Not Found: data tidak ditemukan', 404);
        }

        $dataSurat = is_array($ajuanSurat->data_surat)
            ? $ajuanSurat->data_surat
            : json_decode($ajuanSurat->data_surat, true);

        $kodeSurat = optional($ajuanSurat->surat)->kode_surat ?? 'default';
        $template = 'surat.templates.' . strtolower($kodeSurat);

        if (!view()->exists($template)) {
            return response("Template surat tidak ditemukan", 500);
        }

        $html = view($template, [
            'ajuan' => $ajuanSurat,
            'user' => $ajuanSurat->user,
            'profile' => $ajuanSurat->user->profileMasyarakat,
            'data' => $dataSurat,
        ])->render();

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    public function rejectedStatusPengajuan($slug, $ajuanId){
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('staff-desa')) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk menolak pengajuan surat ini'], 403);
        }

        $pengajuanSurat = Ajuan::with(['user', 'surat'])
            ->where('id', $ajuanId)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$pengajuanSurat) {
            return response()->json(['error' => 'Pengajuan surat tidak ditemukan'], 404);
        }

        if ($pengajuanSurat->status !== 'processed') {
            return response()->json(['error' => 'Pengajuan surat tidak dalam status yang dapat ditolak'], 400);
        }

        $pengajuanSurat->status = 'rejected';
        $pengajuanSurat->save();

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'tolak_pengajuan_surat',
            'description' => 'Pengajuan surat dengan ID ' . $pengajuanSurat->id . ' telah ditolak.',
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Pengajuan surat berhasil ditolak.',
            'pengajuan_surat' => $pengajuanSurat,
        ], 200);    
    }


   public function confirmedStatusPengajuan($slug, $ajuanId){
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('staff-desa')) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengonfirmasi pengajuan surat ini'], 403);
        }

        $pengajuanSurat = Ajuan::with(['user', 'surat'])
            ->where('id', $ajuanId)
            ->whereHas('surat', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->first();

        if (!$pengajuanSurat) {
            return response()->json(['error' => 'Pengajuan surat tidak ditemukan'], 404);
        }

        if ($pengajuanSurat->status !== 'processed') {
            return response()->json(['error' => 'Pengajuan surat tidak dalam status yang dapat dikonfirmasi'], 400);
        }

        $pengajuanSurat->status = 'confirmed';
        $pengajuanSurat->save();

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'konfirmasi_pengajuan_surat',
            'description' => 'Pengajuan surat dengan ID ' . $pengajuanSurat->id . ' telah dikonfirmasi.',
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Pengajuan surat berhasil dikonfirmasi.',
            'pengajuan_surat' => $pengajuanSurat,
        ], 200);    
   }

 public function signedStatusPengajuan($slug, $ajuanId)
{
    $kepdes = JWTAuth::parseToken()->authenticate();

    if (!$kepdes) {
        return response()->json(['error' => 'Kepala Desa belum login. Silakan login terlebih dahulu'], 401);
    }

    if (!$kepdes->hasRole('kepala-desa')) {
        return response()->json(['error' => 'Akses ditolak. Anda bukan kepala desa.'], 403);
    }

    $ajuan = Ajuan::with(['user.profileMasyarakat', 'surat', 'tandaTangan'])
        ->where('id', $ajuanId)
        ->whereHas('surat', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })
        ->where('status', 'confirmed')
        ->first();

    if (!$ajuan) {
        return response()->json(['error' => 'Surat tidak ditemukan atau belum dikonfirmasi.'], 404);
    }

    if ($ajuan->tandaTangan) {
        return response()->json(['error' => 'Surat sudah ditandatangani sebelumnya.'], 400);
    }

    // Gunakan waktu konsisten
    $signedAt = now();

    // Path private key
    $privateKeyPath = storage_path('app/keys/private.pem');

    // Debug jika gagal
    if (!file_exists($privateKeyPath) || !is_readable($privateKeyPath)) {
        return response()->json([
            'error' => 'Private key tidak ditemukan atau tidak bisa dibaca.',
            'checked_path' => $privateKeyPath,
            'file_exists' => file_exists($privateKeyPath),
            'is_readable' => is_readable($privateKeyPath),
            'base_path' => base_path()
        ], 500);
    }

    // Baca key
    $privateKey = file_get_contents($privateKeyPath);
    $privateKeyRes = openssl_pkey_get_private($privateKey);

    if (!$privateKeyRes) {
        return response()->json(['error' => 'Format private key tidak valid.'], 500);
    }

    // Data yang akan ditandatangani
    $signatureData = json_encode([
        'ajuan_id' => $ajuan->id,
        'nomor_surat' => $ajuan->nomor_surat,
        'data_surat' => $ajuan->data_surat,
        'user_id' => $ajuan->user_id,
        'timestamp' => $signedAt->toIso8601String(),
    ]);

    // Proses tanda tangan
    openssl_sign($signatureData, $signature, $privateKeyRes, OPENSSL_ALGO_SHA256);
    $encodedSignature = base64_encode($signature);

    // Simpan ke database
    TandaTangan::create([
        'id' => Str::uuid(),
        'ajuan_id' => $ajuan->id,
        'signed_by' => $kepdes->id,
        'signature' => $encodedSignature,
        'signature_data' => $signatureData,
        'signed_at' => $signedAt,
    ]);

    $ajuan->update(['status' => 'approved']);

    // Log aktivitas
    LogActivity::create([
        'id' => Str::uuid(),
        'user_id' => $kepdes->id,
        'activity_type' => 'ttd_surat',
        'description' => 'Surat dengan ID ' . $ajuan->id . ' telah ditandatangani oleh Kepala Desa.',
        'ip_address' => request()->ip(),
    ]);

    // Return berhasil
    return response()->json([
        'message' => 'Surat berhasil ditandatangani.',
        'signed_at' => $signedAt->toIso8601String(),
        'ajuan_id' => $ajuan->id,
        'nomor_surat' => $ajuan->nomor_surat,
        'signed_by' => $kepdes->name,
    ]);
}



public function downloadSurat($slug, $ajuanId)
{
    $user = JWTAuth::parseToken()->authenticate();

    if (!$user->hasAnyRole(['masyarakat', 'staff-desa', 'kepala-desa', 'super-admin'])) {
        return response()->json(['error' => 'Akses ditolak.'], 403);
    }

    $ajuanSurat = Ajuan::with([
        'user.profileMasyarakat',
        'surat',
        'tandaTangan.user'
    ])->where('id', $ajuanId)
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
        return response("Template surat tidak ditemukan", 500);
    }

    // === QR CODE as SVG ===
    $verificationUrl = url("/verifikasi-surat/{$ajuanSurat->id}");
    $qrCodeSvg = QrCode::format('svg')->size(150)->generate($verificationUrl);

    $html = view($template, [
        'ajuan' => $ajuanSurat,
        'user' => $ajuanSurat->user,
        'profile' => $ajuanSurat->user->profileMasyarakat,
        'data' => $dataSurat,
        'qrCodeSvg' => $qrCodeSvg,
        'downloaded_at' => Carbon::now()->translatedFormat('l, d F Y H:i'),
    ])->render();

    ini_set('memory_limit', '-1');

    $pdf = Pdf::loadHTML($html);
    $nomorSurat = preg_replace('/[\/\\\\]/', '-', $ajuanSurat->nomor_surat ?? 'tanpa-nomor');
    return $pdf->download("surat-{$nomorSurat}.pdf");

}





    
}
