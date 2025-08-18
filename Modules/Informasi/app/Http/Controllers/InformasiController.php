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

   public function updateInformasi(Request $request, $id)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user || !$user->hasRole('staff-desa')) {
            return response()->json(['error' => 'Tidak punya akses.'], 403);
        }

        $rules = [
            'judul' => 'nullable|string|max:255',
            'konten' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'kategori' => 'nullable|in:berita,pengumuman,artikel,wisata,produk,banner,galeri',
        ];

        $input = $request->all(); // fix buat form-data
        $validatedData = validator($input, $rules)->validate();

        if ($request->hasFile('gambar')) {
            $validatedData['gambar'] = $request->file('gambar')->store('informasi', 'public');
        }

        $informasi = Informasi::findOrFail($id);
        $validatedData['updated_by'] = $user->id;
        $validatedData['updated_at'] = now();

        $informasi->update($validatedData);

        return response()->json([
            'message' => 'Informasi berhasil diperbarui.',
            'informasi' => $informasi
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Validasi gagal.',
            'details' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Update informasi gagal: ' . $e->getMessage());
        return response()->json([
            'error' => 'Terjadi kesalahan internal. Silakan coba lagi.'
        ], 500);
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
