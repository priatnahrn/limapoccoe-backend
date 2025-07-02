<?php

namespace Modules\PengajuanSurat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\PengajuanSurat\Models\PengajuanSurat;
use Modules\PengajuanSurat\Models\Surat;
use Modules\PengajuanSurat\Models\LogActivity;
use Tymon\JWTAuth\Facades\JWTAuth;

class PengajuanSuratController extends Controller
{
    public function ajukanSurat(Request $request, $suratId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }
        if (!$user->hasRole('masyarakat')) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan masyarakat'], 403);
        } 

        if(!$user->is_profile_complete) {
            return response()->json(['error' => 'Profil belum lengkap. Silakan lengkapi profil terlebih dahulu'], 400);
        }

        $surat = Surat::findOrFail($suratId);
        if (!$surat) {
            return response()->json(['error' => 'Surat tidak ditemukan'], 404);
        }
        // Validasi data yang diperlukan untuk pengajuan surat
        $validatedData = $request->validate([
            'data_surat' => 'required|array',
            'lampiran' => 'nullable|array',
            'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120', // Maksimal 2MB
        ]);

        $pengajuanSurat = PengajuanSurat::create([
            'user_id' => $user->id,
            'surat_id' => $suratId,
            'data_surat' => isset($validatedData['data_surat']) ? json_encode($validatedData['data_surat']) : null,
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

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'ajuan_surat',
            'description' => 'Surat dengan nama ' . $surat->nama_surat . ' telah diajukan.',
            'ip_address' => $request->ip(),
        ]);

        if(!$pengajuanSurat) {
            return response()->json(['error' => 'Gagal mengajukan surat'], 500);
        }

        return response()->json([
            'message' => 'Surat berhasil diajukan.',
            'ajuan_surat' => $pengajuanSurat,
        ], 200);
    }
}
