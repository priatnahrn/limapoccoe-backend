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


class PengajuanSuratController extends Controller
{
    public function ajukanSurat(Request $request, $slug)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('masyarakat')) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan masyarakat'], 403);
        }

        if (!$user->is_profile_complete) {
            return response()->json(['error' => 'Profil belum lengkap. Silakan lengkapi profil terlebih dahulu'], 400);
        }

        $surat = Surat::where('slug', $slug)->first();
        if (!$surat) {
            return response()->json(['error' => 'Surat tidak ditemukan'], 404);
        }

        $validatedData = $request->validate([
            'data_surat' => 'required|array',
            'lampiran' => 'nullable|array',
            'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

        $pengajuanSurat = Ajuan::create([
            'user_id' => $user->id,
            'surat_id' => $surat->id,
            'data_surat' => json_encode($validatedData['data_surat']),
            'status' => 'processed',
        ]);

        if (isset($validatedData['lampiran'])) {
            foreach ($validatedData['lampiran'] as $file) {
                $path = $file->store('lampiran', 'public');
                $pengajuanSurat->lampiran()->create([
                    'file_path' => $path,
                ]);
            }
        }

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'ajuan_surat',
            'description' => 'Surat dengan nama ' . $surat->nama_surat . ' telah diajukan.',
            'ip_address' => $request->ip(),
        ]);

        if (!$pengajuanSurat) {
            return response()->json(['error' => 'Gagal mengajukan surat'], 500);
        }

        return response()->json([
            'message' => 'Surat berhasil diajukan.',
            'ajuan_surat' => $pengajuanSurat,
        ], 200);
    }

    public function getPengajuanSurat($slug)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if ($user->hasRole('masyarakat')) {
            $pengajuanSurat = Ajuan::where('user_id', $user->id)
                ->whereHas('surat', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })
                ->with([
                    'user',                     // wajib agar user muncul
                    'user.profileMasyarakat',  // nested relasi
                    'surat'                    // relasi surat
                ])
                ->get();
        } elseif ($user->hasRole('staff-desa')) {
            $pengajuanSurat = Ajuan::with([
                    'user',
                    'user.profileMasyarakat',
                    'surat'
                ])
                ->whereHas('surat', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })
                ->get();
        } else {
            return response()->json([
                'error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses pengajuan surat ini'
            ], 403);
        }

        if ($pengajuanSurat->isEmpty()) {
            return response()->json(['message' => 'Tidak ada pengajuan surat yang ditemukan'], 200);
        }

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
    
}
