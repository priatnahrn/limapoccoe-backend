<?php

namespace Modules\Pengaduan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pengaduan\Models\Pengaduan;
use Modules\Pengaduan\Transformers\PengaduanResource;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\LogActivity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Pengaduan\Http\Requests\PengaduanRequest;
use Illuminate\Support\Facades\Storage;
use Modules\Pengaduan\Http\Requests\ProcessedAduanRequest;

class PengaduanController extends Controller
{

    public function create(PengaduanRequest $request)
    {
        try {
            // ✅ ASVS 2.1.1 – Validasi token dan autentikasi pengguna
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                // ✅ SCP #122 – Tidak bocorkan detail login
                return response()->json(['message' => 'User belum login.'], 401);
            }

            // ✅ ASVS 5.1.6 – Role-based access control (RBAC)
            if (!$user->hasRole('masyarakat')) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            // ✅ ASVS 5.1.3 – Validasi input trusted dari sisi server (pakai FormRequest)
            $validated = $request->validated();

            // ✅ ASVS 13.2.1 – Validasi dan pembatasan upload file (tipe dan ukuran)
            $evidencePath = null;
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store('aduan/evidence', 'public');
                // ✅ SCP #37 – Simpan file di lokasi aman dan terbatas (storage/public)
            }

            DB::beginTransaction();

            // ✅ ASVS 1.7 – Data hanya dibuat atas nama user yang sedang login
            $aduan = Pengaduan::create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'location' => $validated['location'] ?? null,
                'category' => $validated['category'],
                'evidence' => $evidencePath,
                'status' => 'waiting', // ✅ ASVS 13.4.1 – Default state bukan "approved"
            ]);

            // ✅ ASVS 7.1.3 – Logging aktivitas penting untuk audit trail
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'create_pengaduan',
                'description' => "User {$user->name} membuat aduan ID {$aduan->id}",
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            // ✅ SCP #122 – Response tidak expose informasi sensitif
            return response()->json([
                'message' => 'Aduan berhasil dibuat.',
                'aduan' => new PengaduanResource($aduan->load('user')), // ✅ Gunakan resource untuk kontrol output
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            // ✅ SCP #107 – Log detail error internal (tanpa expose ke client)
            Log::error('Gagal membuat pengaduan', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat aduan.' // ✅ SCP #110 – Jangan bocorkan stacktrace
            ], 500);
        }
    }

    public function getAllAduan()
    {
        try {
            // ✅ ASVS 2.1.1 – Validasi token autentikasi
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                // ✅ SCP #122 – Jangan bocorkan info teknis
                return response()->json(['message' => 'User belum login.'], 401);
            }

            $aduan = collect(); // default empty

            // ✅ ASVS 5.1.6 – Role-based access control (RBAC)

            if ($user->hasRole('masyarakat')) {
                // ✅ ASVS 1.7 – User hanya boleh lihat milik sendiri
                $aduan = Pengaduan::where('user_id', $user->id)->latest()->get();

            } elseif ($user->hasAnyRole(['staff-desa', 'super-admin'])) {
                // ✅ ASVS 1.5.3 – Admin boleh lihat semua data
                $aduan = Pengaduan::with('user')->latest()->get();

            } elseif ($user->hasRole('kepala-desa')) {
                // ✅ ASVS 1.5.3 – Role tertentu hanya akses data terbatas
                $aduan = Pengaduan::with('user')
                    ->whereIn('status', ['processed', 'approved'])
                    ->latest()
                    ->get();

            } else {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            // ✅ SCP #110 – Tangani response kosong secara eksplisit
            if ($aduan->isEmpty()) {
                return response()->json(['message' => 'Tidak ada aduan yang ditemukan.'], 404);
            }

            // ✅ ASVS 7.1.3 – Logging untuk audit trail
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'view_pengaduan',
                'description' => "{$user->name} melihat daftar aduan.",
                'ip_address' => request()->ip(),
            ]);

            // ✅ ASVS 8.4 / SCP #122 – Gunakan resource untuk mengontrol output
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar aduan.',
                'total' => $aduan->count(),
                'aduan' => PengaduanResource::collection($aduan),
            ], 200);

        } catch (\Throwable $e) {
            // ✅ SCP #107 – Log error internal
            Log::error('Gagal mengambil data aduan', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data aduan.'
            ], 500);
        }
    }


    public function getDetailAduan($aduan_id)
    {
        try {
            // ✅ ASVS 2.1.1 – Validasi token JWT untuk autentikasi user
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['message' => 'User belum login.'], 401);
            }

            $aduan = null;

            // ✅ ASVS 5.1.6 – Role-based Access Control
            if ($user->hasRole('masyarakat')) {
                // ✅ ASVS 1.7 – Batasi akses hanya ke data milik sendiri
                $aduan = Pengaduan::where('id', $aduan_id)
                    ->where('user_id', $user->id)
                    ->first();
            } elseif ($user->hasAnyRole(['super-admin', 'staff-desa', 'kepala-desa'])) {
                // ✅ ASVS 1.5.3 – Admin boleh akses semua data
                $aduan = Pengaduan::with('user')->find($aduan_id);
            } else {
                // ✅ SCP #122 – Jangan bocorkan info detail akses
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            if (!$aduan) {
                // ✅ SCP #114 – Tangani data tidak ditemukan dengan response aman
                return response()->json(['message' => 'Aduan tidak ditemukan.'], 404);
            }

            // ✅ ASVS 7.1.3 – Logging akses data untuk audit trail
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'view_detail_aduan',
                'description' => "User {$user->name} melihat detail aduan ID {$aduan->id}",
                'ip_address' => request()->ip(),
            ]);

            // ✅ ASVS 8.4 – Gunakan Resource untuk proteksi output & konsistensi struktur
            return response()->json([
                'message' => 'Berhasil mendapatkan detail aduan.',
                'user' => $user->only(['id', 'name', 'nik']),
                'aduan' => new PengaduanResource($aduan),
            ], 200);

        } catch (\Throwable $e) {
            // ✅ SCP #107 – Log detail error internal (tanpa bocorkan ke client)
            Log::error('Gagal mengambil detail aduan', [
                'user_id' => $user->id,
                'aduan_id' => $aduan_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil detail aduan.'
            ], 500);
        }
    }

    
    public function processedStatusAduan(ProcessedAduanRequest $request, $aduan_id)
    {
        try {
            // ✅ ASVS 2.1.1 – Autentikasi token JWT
            $admin = JWTAuth::parseToken()->authenticate();

            if (!$admin || !$admin->hasRole('staff-desa')) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            $aduan = Pengaduan::with('user', 'responseBy')->find($aduan_id);

            // ✅ ASVS 5.1.3 – Validasi eksistensi data
            if (!$aduan) {
                return response()->json(['message' => 'Aduan tidak ditemukan.'], 404);
            }

            // ✅ ASVS 4.3.4 – Cegah modifikasi ganda
            if ($aduan->status !== 'waiting') {
                return response()->json(['message' => 'Aduan sudah diproses.'], 400);
            }

            // ✅ SCP #10 – Simpan hanya data yang tervalidasi
            $aduan->update([
                'response' => $request->validated()['response'],
                'response_by' => $admin->id,
                'response_date' => now(),
                'status' => 'processed',
            ]);

            // ✅ ASVS 7.1.3 – Logging audit trail
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'processed_aduan',
                'description' => "Aduan ID {$aduan->id} diproses oleh {$admin->name}",
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Berhasil memproses aduan.',
                'aduan' => new PengaduanResource($aduan),
            ]);

        } catch (\Throwable $e) {
            Log::error('Processed Aduan Error', [
                'aduan_id' => $aduan_id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Terjadi kesalahan saat memproses aduan.'], 500);
        }
    }


    public function approvedStatusAduan($aduan_id)
    {
        try {
            // ✅ ASVS 2.1.1 – Autentikasi pengguna dengan JWT
            $admin = JWTAuth::parseToken()->authenticate();

            if (!$admin) {
                return response()->json(['message' => 'Admin belum login.'], 401);
            }

            // ✅ ASVS 5.1.6 – Role-based Access Control (RBAC)
            if (!$admin->hasRole('staff-desa')) {
                return response()->json(['message' => 'Akses ditolak. Hanya staff desa yang dapat menyetujui aduan.'], 403);
            }

            // ✅ ASVS 5.1.3 – Validasi keberadaan data
            $aduan = Pengaduan::with('user', 'responseBy')->find($aduan_id);

            if (!$aduan) {
                return response()->json(['message' => 'Aduan tidak ditemukan.'], 404);
            }

            // ✅ ASVS 4.3.4 – Validasi status sebelum approve
            if ($aduan->status !== 'processed') {
                return response()->json(['message' => 'Aduan belum diproses, tidak dapat disetujui.'], 400);
            }

            // ✅ SCP #10 – Lakukan update hanya pada field yang diizinkan
            $aduan->update(['status' => 'approved']);

            // ✅ ASVS 7.1.3 – Logging untuk audit trail
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'activity_type' => 'approved_aduan',
                'description' => "Aduan ID {$aduan->id} disetujui oleh {$admin->name}",
                'ip_address' => request()->ip(),
            ]);

            // ✅ ASVS 8.4 – Gunakan resource untuk response yang konsisten dan aman
            return response()->json([
                'message' => 'Aduan berhasil disetujui.',
                'aduan' => new PengaduanResource($aduan),
            ], 200);

        } catch (\Throwable $e) {
            // ✅ SCP #107 – Jangan bocorkan error detail ke client
            Log::error('Gagal menyetujui aduan', [
                'user_id' => $admin->id,
                'aduan_id' => $aduan_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan saat menyetujui aduan.'], 500);
        }
    }
    
}
