<?php

namespace Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Profile\Models\ProfileMasyarakat;
use Modules\Auth\Http\Resources\AuthUserResource;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{
    public function cekProfilMasyarakat(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }
        if (!$user->hasRole('masyarakat')) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan masyarakat'], 403);
        }

        return response()->json([
            'message' => 'Berhasil mengakses profil.',
            'user_id' => $user->id,
            'is_profile_complete' => $user->is_profile_complete,
            'user_data' => $user
        ]);
    }

    public function getProfileDataMasyarakat()
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

        $profile = ProfileMasyarakat::where('user_id', $user->id)->first();

        return response()->json([
            'message' => 'Berhasil mendapatkan data profile.',
            'profile' => $profile,
            'user' => $user,
        ], 200);
    }
    public function lengkapiProfilMasyarakat(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('masyarakat')) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan masyarakat'], 403);
        }

        if ($user->is_profile_complete) {
            return response()->json(['error' => 'Profil sudah lengkap.'], 400);
        }

        $validated = $request->validate([
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'dusun' => 'required|in:WT.Bengo,Barua,Mappasaile,Kampala,Kaluku,Jambua,Bontopanno,Samata',
            'pekerjaan' => 'nullable|string|max:100',
            'rt_rw' => 'nullable|string|max:12',
            'alamat' => 'required|string',
        ]);

        $profileUser = ProfileMasyarakat::create([
            'user_id' => $user->id,
            'tempat_lahir' => $validated['tempat_lahir'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'dusun' => $validated['dusun'],
            'pekerjaan' => $validated['pekerjaan'] ?? null,
            'rt_rw' => $validated['rt_rw'] ?? null,
            'alamat' => $validated['alamat'],
        ]);

        if (!$profileUser) {
            return response()->json(['error' => 'Gagal menyimpan profil. Silakan coba lagi.'], 500);
        }

        $user->is_profile_complete = true;
        $user->save();

        return response()->json([
            'message' => 'Berhasil menyimpan profil.',
            'user_id' => $user->id,
            'is_profile_complete' => $user->is_profile_complete,
            'user_data' => $user,
            'profile' => $profileUser
        ]);
    }

    public function updateProfileMasyarakat(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['error' => 'User belum login. Silakan login terlebih dahulu'], 401);
        }

        if (!$user->hasRole('masyarakat')) {
            return response()->json(['error' => 'Akses ditolak. Anda bukan masyarakat'], 403);
        }

        $profile = ProfileMasyarakat::where('user_id', $user->id)->first();
        if (!$profile) {
            return response()->json(['error' => 'Profil tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'dusun' => 'required|in:WT.Bengo,Barua,Mappasaile,Kampala,Kaluku,Jambua,Bontopanno,Samata',
            'alamat' => 'required|string',
        ]);

        $profile->update($validated);

        return response()->json([
            'message' => 'Berhasil memperbarui profil.',
            'profile' => $profile,
            'user_data' => new AuthUserResource($user),
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('profile::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('profile::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('profile::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('profile::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
