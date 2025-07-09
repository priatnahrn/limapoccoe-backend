<?php

namespace Modules\PengajuanSurat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Modules\PengajuanSurat\Models\Surat;
use Modules\PengajuanSurat\Models\ActivityLog;
use Tymon\JWTAuth\Facades\JWTAuth;


class SuratController extends Controller
{

     public function getAllSurat()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }
        $surat = Surat::all();

        return response()->json([
            'message' => "Berhasil mendapatkan semua data jenis surat.",
            'jenis_surat' => $surat,
        ], 200);
    }

    public function getDetailSurat($surat_id)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        $surat = Surat::where('id', $surat_id)->first();

        return response()->json([
            'message' => "Berhasil mendapatkan detail data jenis surat.",
            'jenis_surat' => $surat,
        ], 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'kode_surat' => 'required|max:6|min:2',
            'nama_surat' => 'required|max:255|min:12',
            'deskripsi' => 'required|max:255|min:12',
            'syarat_ketentuan' => 'required|min:12',
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('staff_desa') || !$user->hasRole('super_admin')) {
            return response()->json(['error' => 'User bukan admin. Anda tidak memiliki akses ke fitur ini'], 401);
        }

        $surat = Surat::create([
            'kode_surat' => request('kode_surat', $validated['kode_surat']),
            'nama_surat' => request('nama_surat', $validated['nama_surat']),
            'deskripsi' => request('deskripsi', $validated['deskripsi']),
            'syarat_ketentuan' => request('syarat_ketentuan', $validated['syarat_ketentuan']),
        ]);

        $surat->save();

        $log = LogActivity::create([
            'user_id' => $user->id,
            'message' => 'User membuat jenis surat baru: ' . $surat->nama_surat
        ]);

        $log->save();

        return response()->json([
            'message' => "Berhasil membuat jenis surat baru.",
            'jenis_surat' => $surat,
        ], 200);
    }

    public function update(Request $request, $surat_id)
    {
        $validated = $request->validate([
            'kode_surat' => 'required|max:6|min:2',
            'nama_surat' => 'required|max:255|min:12',
            'deskripsi' => 'required|max:255|min:12',
            'syarat_ketentuan' => 'required|min:12',
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('staff_desa') || !$user->hasRole('super_admin')) {
            return response()->json(['error' => 'User bukan admin. Anda tidak memiliki akses ke fitur ini'], 401);
        }

        $surat = Surat::findOrFail($surat_id);
        $surat->update($validated);

        $log = LogActivity::create([
            'user_id' => $user->id,
            'message' => 'User mengupdate jenis surat: ' . $surat->nama_surat
        ]);

        $log->save();

        return response()->json([
            'message' => "Berhasil mengupdate jenis surat.",
            'jenis_surat' => $surat,
        ], 200);
    }

    public function delete($surat_id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('staff_desa') || !$user->hasRole('super_admin')) {
            return response()->json(['error' => 'User bukan admin. Anda tidak memiliki akses ke fitur ini'], 401);
        }

        $surat = Surat::findOrFail($surat_id);
        $surat->delete();

        $log = LogActivity::create([
            'user_id' => $user->id,
            'message' => 'User menghapus jenis surat: ' . $surat->nama_surat
        ]);

        $log->save();

        return response()->json([
            'message' => "Berhasil menghapus jenis surat.",
            'jenis_surat' => $surat,
        ], 200);
    }

}
