<?php

namespace Modules\Informasi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Modules\Informasi\Http\Requests\InformasiRequest;
use Modules\Informasi\Models\Informasi;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InformasiController extends Controller
{
    public function tambahInformasi(InformasiRequest $request)
    {
        try {
            // ✅ Autentikasi dan role check
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['error' => 'Anda belum login.'], 401);
            }

            if (!$user->hasRole('staff-desa')) {
                return response()->json(['error' => 'Anda tidak memiliki izin.'], 403);
            }

            // ✅ Validasi data
            $data = $request->safe()->except('gambar');

            // ✅ Upload gambar jika ada
            if ($request->hasFile('gambar')) {
                $data['gambar'] = $request->file('gambar')->store('informasi', 'public');
            }

            // ✅ Audit field
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;

            // ✅ Simpan ke database
            $informasi = Informasi::create($data);

            // ✅ Response
            return response()->json([
                'message'   => 'Informasi berhasil ditambahkan.',
                'informasi' => $informasi
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error'   => 'Validasi gagal.',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Tambah informasi gagal: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan internal. Silakan coba lagi.'
            ], 500);
        }
    }

    public function updateInformasi(InformasiRequest $request, $id)
    {
        try {
            // 🔐 Autentikasi & role check
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user || !$user->hasRole('staff-desa')) {
                return response()->json(['error' => 'Tidak punya akses.'], 403);
            }

            // 🔎 Ambil data dari DB
            $informasi = Informasi::findOrFail($id);

            // 🧹 Siapkan data yang mau diupdate (hanya isi yang dikirim)
            $payload = [];

            if ($request->filled('judul')) {
                $payload['judul'] = $request->input('judul');

                // 🔁 Kalau judul berubah, update slug juga
                if ($informasi->judul !== $request->input('judul')) {
                    $payload['slug'] = Informasi::generateUniqueSlug($request->input('judul'), $informasi->id);
                }
            }

            if ($request->filled('konten')) {
                $payload['konten'] = $request->input('konten');
            }

            if ($request->filled('kategori')) {
                $payload['kategori'] = $request->input('kategori');
            }

            $payload['updated_by'] = $user->id;

            // 📸 Ganti gambar jika ada
            if ($request->hasFile('gambar')) {
                if ($informasi->gambar) {
                    Storage::disk('public')->delete($informasi->gambar);
                }
                $payload['gambar'] = $request->file('gambar')->store('informasi', 'public');
            }

            // 💾 Update ke DB
            $informasi->update($payload);
            $informasi->refresh();

            // ✅ Respon
            return response()->json([
                'message' => 'Informasi berhasil diperbarui.',
                'informasi' => $informasi
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validasi gagal.', 'details' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Informasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Update informasi gagal: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan internal. Silakan coba lagi.'], 500);
        }
    }







    public function deleteInformasi($id)
    {
        try {
            // ✅ Autentikasi dan role check
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['error' => 'Anda belum login.'], 401);
            }

            if (!$user->hasRole('staff-desa')) {
                return response()->json(['error' => 'Anda tidak memiliki izin.'], 403);
            }

            // ✅ Cari informasi berdasarkan ID
            $informasi = Informasi::findOrFail($id);

            // ✅ Hapus informasi
            $informasi->delete();

            // ✅ Response
            return response()->json([
                'message' => 'Informasi berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Hapus informasi gagal: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan internal. Silakan coba lagi.'
            ], 500);
        }
    }

    public function getAllInformasiAdmin()
    {
        try {
            // ✅ Autentikasi
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['error' => 'Anda belum login.'], 401);
            }

            // ✅ Cek role
            if (!$user->hasRole('staff-desa')) {
                return response()->json(['error' => 'Anda tidak memiliki izin.'], 403);
            }

            // ✅ Ambil semua informasi
            $informasi = Informasi::all();

            // ✅ Response
            return response()->json([
                'message' => 'Daftar informasi berhasil diambil.',
                'data' => $informasi
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ambil semua informasi gagal: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan internal. Silakan coba lagi.'
            ], 500);
        }
    }

    public function getDetailInformasiAdmin($id)
    {
        try {
            // ✅ Autentikasi
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['error' => 'Anda belum login.'], 401);
            }

            // ✅ Cek role
            if (!$user->hasRole('staff-desa')) {
                return response()->json(['error' => 'Anda tidak memiliki izin.'], 403);
            }

            // ✅ Cari informasi berdasarkan slug
            $informasi = Informasi::where('id', $id)->firstOrFail();

            // ✅ Response
            return response()->json([
                'message' => 'Detail informasi berhasil diambil.',
                'data' => $informasi
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Informasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Ambil detail informasi gagal: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan internal. Silakan coba lagi.'
            ], 500);
        }
    }


    public function getAllInformasiPublik()
    {
        try {
            // ✅ Ambil semua informasi
            $informasi = Informasi::all();
            // ✅ Response
            return response()->json([
                'message' => 'Daftar informasi berhasil diambil.',
                'data' => $informasi
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ambil semua informasi gagal: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan internal. Silakan coba lagi.'
            ], 500);
        }
    }

    public function getDetailInformasi($slug)
    {
        try {
            // ✅ Cari informasi berdasarkan slug
            $informasi = Informasi::where('slug', $slug)->firstOrFail();

            // ✅ Response
            return response()->json([
                'message' => 'Detail informasi berhasil diambil.',
                'data' => $informasi
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Informasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Ambil detail informasi gagal: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan internal. Silakan coba lagi.'
            ], 500);
        }
    }
}
