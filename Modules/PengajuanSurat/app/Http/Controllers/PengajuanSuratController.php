<?php

namespace Modules\PengajuanSurat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\PengajuanSurat\Models\Ajuan;
use Modules\PengajuanSurat\Models\Surat;
use App\Models\LogActivity;
use Tymon\JWTAuth\Facades\JWTAuth;


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
                ->with('surat', 'profileMasyarakat')
                ->get();
        } elseif ($user->hasAnyRole(['staff-desa'])) {
            $pengajuanSurat = Ajuan::with(['user', 'surat'])
                ->whereHas('surat', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses pengajuan surat ini'], 403);
        }

        if ($pengajuanSurat->isEmpty()) {
            return response()->json(['message' => 'Tidak ada pengajuan surat yang ditemukan'], 200);
        }

        return response()->json([
            'message' => 'Berhasil mendapatkan detail pengajuan surat.',
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

        $pengajuanSurat = Ajuan::with(['user', 'surat'])
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

    public function updateStatusPengajuan(Request $request, $ajuanId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasAnyRole(['staff-desa', 'admin'])) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengupdate status pengajuan surat'], 403);
        }

        $pengajuanSurat = Ajuan::find($ajuanId);
        if (!$pengajuanSurat) {
            return response()->json(['error' => 'Pengajuan surat tidak ditemukan'], 404);
        }

        $validatedData = $request->validate([
            'status' => 'required|string|in:processed,approved,rejected',
        ]);

        $pengajuanSurat->status = $validatedData['status'];
        $pengajuanSurat->save();

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'update_status_pengajuan',
            'description' => 'Status pengajuan surat dengan ID ' . $pengajuanSurat->id . ' telah diupdate menjadi ' . $pengajuanSurat->status,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Status pengajuan surat berhasil diupdate.',
            'pengajuan_surat' => $pengajuanSurat,
        ], 200);
    }

    
}
