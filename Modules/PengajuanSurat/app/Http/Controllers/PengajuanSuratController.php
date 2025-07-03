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
        $authUser = JWTAuth::parseToken()->authenticate();
        if (!$authUser) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        $surat = Surat::where('slug', $slug)->first();
        if (!$surat) {
            return response()->json(['error' => 'Surat tidak ditemukan'], 404);
        }

        $validatedData = $request->validate([
            'data_surat' => 'required|array',
            'lampiran' => 'nullable|array',
            'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
            'user_id' => 'required_if:role,staff-desa|exists:users,id', // hanya untuk staff-desa
        ]);

        // Role: masyarakat
        if ($authUser->hasRole('masyarakat')) {
            if (!$authUser->is_profile_complete) {
                return response()->json(['error' => 'Profil belum lengkap. Silakan lengkapi profil terlebih dahulu'], 400);
            }

            $targetUser = $authUser;
        } elseif ($authUser->hasRole('staff-desa')) {
            $targetUser =AuthUser::find($validatedData['user_id']);

            if (!$targetUser || !$targetUser->hasRole('masyarakat')) {
                return response()->json(['error' => 'User yang dipilih bukan masyarakat'], 400);
            }
        }
        else {
            return response()->json(['error' => 'Akses ditolak. Anda tidak berhak mengajukan surat'], 403);
        }

        // Simpan ajuan
        $pengajuanSurat = Ajuan::create([
            'user_id' => $targetUser->id,
            'surat_id' => $surat->id,
            'data_surat' => json_encode($validatedData['data_surat']),
            'status' => 'processed',
        ]);

        // Simpan lampiran jika ada
        if (isset($validatedData['lampiran'])) {
            foreach ($validatedData['lampiran'] as $file) {
                $path = $file->store('lampiran', 'public');
                $pengajuanSurat->lampiran()->create([
                    'file_path' => $path,
                ]);
            }
        }

        // Log aktivitas
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $authUser->id,
            'activity_type' => 'ajuan_surat',
            'description' => 'Surat "' . $surat->nama_surat . '" diajukan untuk ' . $targetUser->name,
            'ip_address' => $request->ip(),
        ]);

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

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'get_pengajuan_surat',
            'description' => 'Daftar pengajuan surat dengan slug ' . $slug . ' telah diakses.',
            'ip_address' => request()->ip(),
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

    public function previewSurat($surat_id, $ajuan_id)
    {
        $token = request()->query('token'); // 🔑 Ambil token dari query string

        if (!$token) {
            return response('Unauthorized: token tidak ditemukan', 401);
        }

        try {
            $admin = JWTAuth::setToken($token)->authenticate();
        } catch (\Exception $e) {
            return response('Unauthorized: token tidak valid', 401);
        }

        if (!$admin->hasAnyRole(['super_admin', 'staff_desa', 'kepala_desa'])) {
            return response('Forbidden: bukan admin', 403);
        }

        $ajuanSurat = AjuanSurat::with(['user.profile', 'surat'])
            ->where('surat_id', $surat_id)
            ->where('id', $ajuan_id)
            ->first();

        if (!$ajuanSurat) {
            return response('Not Found: data tidak ditemukan', 404);
        }

        $dataSurat = is_array($ajuanSurat->data_surat)
            ? $ajuanSurat->data_surat
            : json_decode($ajuanSurat->data_surat, true);

        $template = 'surat.templates.' . strtolower($ajuanSurat->surat->kode_surat ?? 'default');

        if (!view()->exists($template)) {
            return response("Template surat untuk kode tidak ditemukan", 500);
        }

        $html = View::make($template, [
            'ajuan' => $ajuanSurat,
            'user' => $ajuanSurat->user,
            'profile' => $ajuanSurat->user->profile,
            'data' => $dataSurat,
        ])->render();

        return response($html, 200)->header('Content-Type', 'text/html');
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
