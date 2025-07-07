<?php

namespace Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Profile\Models\ProfileMasyarakat;
use Modules\Auth\Http\Resources\AuthUserResource;
use Tymon\JWTAuth\Facades\JWTAuth;
use Modules\Profile\Http\Requests\ProfileMasyarakatRequest;
use App\Models\LogActivity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Storage;


class ProfileController extends Controller
{
    public function lengkapiProfilMasyarakat(ProfileMasyarakatRequest $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['message' => 'User belum login.'], 401);
            }

            if (!$user->hasRole('masyarakat')) {
                return response()->json(['message' => 'Akses ditolak. Anda bukan masyarakat.'], 403);
            }

            if ($user->is_profile_complete) {
                return response()->json(['message' => 'Profil sudah lengkap.'], 400);
            }

            // ✅ ASVS 5.1.3 – Validasi input trusted di sisi server
            $validated = $request->validated();

            DB::beginTransaction();

            // ✅ ASVS 1.7 – Pastikan hanya profil user sendiri yang dibuat
            $profile = ProfileMasyarakat::create([
                'user_id' => $user->id,
                'tempat_lahir' => $validated['tempat_lahir'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'dusun' => $validated['dusun'],
                'pekerjaan' => $validated['pekerjaan'] ?? null,
                'rt_rw' => $validated['rt_rw'] ?? null,
                'alamat' => $validated['alamat'],
            ]);

            $user->is_profile_complete = true;
            $user->save();

            DB::commit();

            return response()->json([
                'message' => 'Profil berhasil disimpan.',
                'user_id' => $user->id,
                'is_profile_complete' => true,
                'profile' => $profile,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Gagal menyimpan profil', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan profil.'], 500);
        }
    }

    public function getProfileDataMasyarakat()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // ✅ ASVS 2.1.1 – Validasi autentikasi token
            if (!$user) {
                return response()->json(['message' => 'User belum login.'], 401);
            }

            // ✅ ASVS 5.1.6 – Validasi akses berdasarkan role
            if (!$user->hasRole('masyarakat')) {
                return response()->json(['message' => 'Akses ditolak. Anda bukan masyarakat.'], 403);
            }

            // ✅ ASVS 13.2.2 – Validasi status data bisnis (profil lengkap)
            if (!$user->is_profile_complete) {
                return response()->json(['message' => 'Profil belum lengkap. Silakan lengkapi terlebih dahulu.'], 400);
            }

            // ✅ ASVS 1.7 – Batasi data profil hanya milik user terkait
            $profile = ProfileMasyarakat::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json(['message' => 'Data profil tidak ditemukan.'], 404);
            }

            return response()->json([
                'message' => 'Berhasil mendapatkan data profil.',
                'profile' => $profile,
                'user' => $user->only(['id', 'name', 'nik', 'no_whatsapp', 'roles']),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gagal mengambil data profil', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan saat mengambil data.'], 500);
        }
    }


    public function updateProfileMasyarakat(ProfileMasyarakatRequest $request)
    {
        try {
            // ✅ ASVS 2.1.1 – Autentikasi via token JWT
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['message' => 'User belum login.'], 401);
            }

            // ✅ ASVS 5.1.6 – Role-based access control
            if (!$user->hasRole('masyarakat')) {
                return response()->json(['message' => 'Akses ditolak. Anda bukan masyarakat.'], 403);
            }

            // ✅ ASVS 1.7 – Validasi kepemilikan data
            $profile = ProfileMasyarakat::where('user_id', $user->id)->first();
            if (!$profile) {
                return response()->json(['message' => 'Profil tidak ditemukan.'], 404);
            }

            $validated = $request->validated();

            DB::beginTransaction();

            $profile->update([
                'tempat_lahir' => $validated['tempat_lahir'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'dusun' => $validated['dusun'],
                'pekerjaan' => $validated['pekerjaan'] ?? null,
                'rt_rw' => $validated['rt_rw'] ?? null,
                'alamat' => $validated['alamat'],
            ]);

            // ✅ ASVS 7.1.3 – Logging perubahan data penting
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'update_profile',
                'description' => 'User memperbarui data profil masyarakat.',
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Profil berhasil diperbarui.',
                'user_id' => $user->id,
                'profile' => $profile,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Gagal memperbarui profil', ['user_id' => $user->id ?? null, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan saat memperbarui profil.'], 500);
        }
    }

    
}
