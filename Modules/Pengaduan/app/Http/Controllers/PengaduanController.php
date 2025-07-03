<?php

namespace Modules\Pengaduan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pengaduan\Models\Pengaduan;
use Modules\Pengaduan\Http\Resources\AduanResource;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\LogActivity;
use Illuminate\Support\Str;

class PengaduanController extends Controller
{

    public function create(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('masyarakat')) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan masyarakat'], 401);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'location' => 'nullable|string|max:255',
            'category' => 'required|in:Administrasi,Infrastruktur & Fasilitas,Kesehatan,Keamanan & Ketertiban,Pendidikan,Lingkungan,Kinerja Perangkat Desa,Ekonomi & Pekerjaan,Teknologi,Lainnya',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Simpan file evidence jika ada
        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('aduan/evidence', 'public');
        }

        $aduan = Pengaduan::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'location' => $validated['location'] ?? null,
            'category' => $validated['category'] ?? null,
            'evidence' => $evidencePath,
            'status' => 'waiting',
        ]);

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'created_aduan',
            'description' => "Aduan ID {$aduan->id} telah dibuat oleh {$user->name}",
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Aduan berhasil dibuat.',
            'aduan' => $aduan
        ], 201);
    }

    public function getAllAduan()
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        // Masyarakat: hanya lihat aduan miliknya
        if ($user->hasRole('masyarakat')) {
            $aduan = Pengaduan::where('user_id', $user->id)->get();
        }
        // Admin atau Kepala Desa: lihat semua
        elseif ($user->hasAnyRole(['staff-desa', 'kepala-desa'])) {
            $aduan = Pengaduan::with('user')->get();
        }
        // Role lain: tidak diizinkan
        else {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        if ($aduan->isEmpty()) {
            return response()->json(['message' => 'Tidak ada aduan yang ditemukan'], 404);
        }

        // Log activity
        LogActivity::create([
            'user_id' => $user->id,
            'activity_type' => 'viewed_all_aduan',
            'description' => "Aduan telah dilihat oleh {$user->name}",
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Berhasil mendapatkan daftar aduan.',
            'aduan' => $aduan,
        ], 200);
    }

     public function getDetailAduan($aduan_id)
    {
       $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if($user->hasRole('masyarakat')) {
            $aduan = Pengaduan::where('id', $aduan_id)->where('user_id', $user->id)->first();
        } elseif ($user->hasAnyRole(['super-admin', 'staff-desa'])) {
            $aduan = Pengaduan::with('user')->where('id', $aduan_id)->first();
        } else {
            return response()->json(['error' => 'Akses ditolak. Anda bukan admin atau masyarakat'], 403);
        }

        if (!$aduan) {
            return response()->json(['error' => 'Aduan tidak ditemukan'], 404);
        }

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'viewed-aduan',
            'description' => "Aduan ID {$aduan->id} telah dilihat oleh {$user->name}",
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Berhasil mendapatkan detail aduan.',
            'aduan' => $aduan,
        ], 200);
    }

    
    public function processedStatusAduan(Request $request, $aduan_id)
    {
        $validated = $request->validate([
            'response' => 'required|string',
        ]);

        $admin = JWTAuth::parseToken()->authenticate();
        if (!$admin) {
            return response()->json(['error' => 'Admin belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$admin->hasRole(['staff-desa'])) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan admin'], 403);
        }

        $aduan = Pengaduan::with('user', 'responseBy')->where('id', $aduan_id)->first();

        if (!$aduan) {
            return response()->json(['error' => 'Aduan tidak ditemukan'], 404);
        }
        if ($aduan->status !== 'waiting') {
            return response()->json(['error' => 'Aduan sudah diproses sebelumnya'], 400);
        }

        
        $aduan->response = $validated['response'];
        $aduan->response_by = $admin->id;
        $aduan->response_date = now();
        $aduan->status = 'processed';
        $aduan->save();
        // Log activity
        
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $admin->id,
            'activity_type' => 'processed-aduan',
            'description' => "Aduan ID {$aduan->id} telah diproses oleh {$admin->name}",
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Berhasil memproses aduan.',
            'aduan' => $aduan,
        ], 200);
    }

    public function approvedStatusAduan($aduan_id)
    {
        $admin = JWTAuth::parseToken()->authenticate();
        if (!$admin) {
            return response()->json(['error' => 'Admin belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$admin->hasRole('staff-desa')) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan admin'], 403);
        }

        $aduan = Pengaduan::with('user', 'responseBy')->where('id', $aduan_id)->first();

        if (!$aduan) {
            return response()->json(['error' => 'Aduan tidak ditemukan'], 404);
        }

        if ($aduan->status !== 'processed') {
            return response()->json(['error' => 'Aduan belum diproses'], 400);
        }

        $aduan->status = 'approved';
        $aduan->save();


        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $admin->id,
            'activity_type' => 'approved_aduan',
            'activity' => 'approved aduan',
            'description' => "Aduan ID {$aduan->id} telah disetujui oleh {$admin->name}",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Berhasil memproses aduan.',
            'aduan' => $aduan
        ], 200);
    }


    
   
    
}
