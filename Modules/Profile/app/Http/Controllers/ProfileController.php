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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Auth\Transformers\AuthResource;
use Modules\Profile\Models\ProfileStaff;
use Modules\Profile\Transformers\ProfileMasyarakatResource;
use Throwable;

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
                'message' => 'Profil berhasil disimpan. Anda telah dapat menggunakan layanan kami.',
                'profile' => new ProfileMasyarakatResource($profile),
                'user' => new AuthResource($user),
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan profil', ['error' => $e->getMessage()]);
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
                'profile' => new ProfileMasyarakatResource($profile),
                'user' => new AuthResource($user),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil data profil', ['error' => $e->getMessage()]);
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
            Log::error('Gagal memperbarui profil', ['user_id' => $user->id ?? null, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan saat memperbarui profil.'], 500);
        }
    }


    // public function updateProfileStaff(Request $request)
    // {
    //     try {
    //         // ✅ ASVS 2.1.1 – Autentikasi via token JWT
    //         $user = JWTAuth::parseToken()->authenticate();

    //         if (!$user) {
    //             return response()->json(['message' => 'User belum login.'], 401);
    //         }

    //         // ✅ ASVS 5.1.6 – Role-based access control
    //         if (!$user->hasRole('staff')) {
    //             return response()->json(['message' => 'Akses ditolak. Anda bukan staff.'], 403);
    //         }

    //         // ✅ ASVS 1.7 – Validasi kepemilikan data
    //         $profile = ProfileStaff::where('user_id', $user->id)->first();
    //         if (!$profile) {
    //             return response()->json(['message' => 'Profil tidak ditemukan.'], 404);
    //         }

    //         $validated = $request->validate([
    //             'nip' => 'nullable|string|max:20',
    //             'tempat_lahir' => 'nullable|string|max:255',
    //             'tanggal_lahir' => 'nullable|date',
    //             'jenis_kelamin' => 'nullable|in:L,P',
    //             'jabatan' => 'nullable|in:"Sekretaris Desa", "Seksi Pemerintahan", "Seksi Kesejahteraan", "Seksi Pelayanan", "Urusan Tata Usaha & Umum", "Urusan Keuangan", "Urusan Perencanaan"',
    //             'alamat' => 'nullable|string|max:500',
    //             'no_telepon' => 'nullable|string|max:20',
    //             'pendidikan_terakhir' => 'nullable|string|max:100',
    //         ]);

    //         DB::beginTransaction();

    //         $profile->update($validated);

    //         // ✅ ASVS 7.1.3 – Logging perubahan data penting
    //         LogActivity::create([
    //             'id' => Str::uuid(),
    //             'user_id' => $user->id,
    //             'activity_type' => 'update_profile_staff',
    //             'description' => 'Staff memperbarui data profil masyarakat.',
    //             'ip_address' => $request->ip(),
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Profil berhasil diperbarui.',
    //             'profile' => $profile,
    //             'user' => new AuthResource($user),
    //         ], 200);

    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Gagal memperbarui profil staff', ['user_id' => $user->id ?? null, 'error' => $e->getMessage()]);
    //         return response()->json(['message' => 'Terjadi kesalahan saat memperbarui profil.'], 500);
    //     }
    // }
public function updateProfileStaff(Request $request)
{
    // Kita inisialisasi di awal supaya aman dipakai di catch
    $authedUserId = null;

    try {
        // ✅ ASVS 2.1.1 – Autentikasi via token JWT
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['message' => 'User belum login.'], 401);
        }
        $authedUserId = $user->id;

        // ✅ ASVS 5.1.6 – Role-based access control (samakan dgn controller lain)
        if (!$user->hasRole('staff-desa')) {
            return response()->json(['message' => 'Akses ditolak. Anda bukan staff.'], 403);
        }

        // ✅ ASVS 1.7 – Validasi kepemilikan data
        // Opsi 1 (strict): tolak jika belum ada profil
        // $profile = ProfileStaff::where('user_id', $user->id)->first();
        // if (!$profile) {
        //     return response()->json(['message' => 'Profil tidak ditemukan.'], 404);
        // }

        // Opsi 2 (recommended UX): auto-buat jika belum ada
        $profile = ProfileStaff::firstOrCreate(
            ['user_id' => $user->id],
            ['id' => (string) Str::uuid()] // kalau pakai UUID di tabel
        );

        // ✅ Validasi input (rapikan rule & enum)
        $validated = $request->validate([
            'nip'                 => ['nullable','string','max:20'],
            'tempat_lahir'        => ['nullable','string','max:255'],
            'tanggal_lahir'       => ['nullable','date'], // format Y-m-d akan otomatis divalidasi jika pakai date
            'jenis_kelamin'       => ['nullable', Rule::in(['Laki-laki','Perempuan'])],
            'jabatan'             => [
                'nullable',
                Rule::in([
                    'Sekretaris Desa',
                    'Seksi Pemerintahan',
                    'Seksi Kesejahteraan',
                    'Seksi Pelayanan',
                    'Urusan Tata Usaha & Umum',
                    'Urusan Keuangan',
                    'Urusan Perencanaan',
                ]),
            ],
            'alamat'              => ['nullable','string','max:500'],
            'no_telepon'          => ['nullable','string','max:20'], // bisa diganti regex: ^[0-9+\-\s]+$
            'pendidikan_terakhir' => ['nullable','string','max:100'],
        ]);

        DB::beginTransaction();

        // ✅ Batasi field yang boleh di-update (defense-in-depth)
        $allowed = [
            'nip','tempat_lahir','tanggal_lahir','jenis_kelamin','jabatan',
            'alamat','no_telepon','pendidikan_terakhir',
        ];
        $updatePayload = array_intersect_key($validated, array_flip($allowed));

        // Simpan perubahan
        $original = $profile->getOriginal(); // untuk audit delta
        $profile->fill($updatePayload);
        $profile->save();

        // Buat ringkasan field yang berubah (opsional, bagus untuk audit)
        $changed = [];
        foreach ($updatePayload as $k => $v) {
            if (($original[$k] ?? null) !== $v) {
                $changed[$k] = ['from' => $original[$k] ?? null, 'to' => $v];
            }
        }

        // ✅ ASVS 7.1.3 – Logging perubahan data penting (perbaiki deskripsi)
        \App\Models\LogActivity::create([
            'id'            => (string) Str::uuid(),
            'user_id'       => $user->id,
            'activity_type' => 'update_profile_staff',
            'description'   => 'Staff memperbarui data profil staff.',
            'metadata'      => !empty($changed) ? json_encode(['changes' => $changed]) : null, // kalau ada kolom metadata (json)
            'ip_address'    => $request->ip(),
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'profile' => $profile->fresh(), // kirim versi terbaru
            'user'    => new AuthResource($user),
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $ve) {
        // Laravel otomatis akan return 422, tapi kalau mau custom:
        if (DB::transactionLevel() > 0) DB::rollBack();
        return response()->json([
            'message' => 'Validasi gagal.',
            'errors'  => $ve->errors(),
        ], 422);
    } catch (\Throwable $e) {
        if (DB::transactionLevel() > 0) DB::rollBack();
        Log::error('Gagal memperbarui profil staff', [
            'user_id' => $authedUserId,
            'error'   => $e->getMessage(),
        ]);
        return response()->json(['message' => 'Terjadi kesalahan saat memperbarui profil.'], 500);
    }
}
    public function getProfileDataStaff()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // ✅ ASVS 2.1.1 – Validasi autentikasi token
            if (!$user) {
                return response()->json(['message' => 'User belum login.'], 401);
            }

            // ✅ ASVS 5.1.6 – Validasi akses berdasarkan role
            if (!$user->hasRole('staff-desa')) {
                return response()->json(['message' => 'Akses ditolak. Anda bukan staff.'], 403);
            }

            // ✅ ASVS 1.7 – Batasi data profil hanya milik user terkait
            $profile = ProfileStaff::where('user_id', $user->id)->first();

            Log::info('Mengambil data profil staff', [
                'user_id' => $user->id,
                'profile_id' => $profile->id ?? null,
            ]);

            if (!$profile) {
                return response()->json(['message' => 'Data profil tidak ditemukan.'], 404);
            }


            return response()->json([
                'message' => 'Berhasil mendapatkan data profil.',
                'profile' => $profile,
                'user' => new AuthResource($user),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil data profil staff', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan saat mengambil data.'], 500);
        }
    }

    
}
