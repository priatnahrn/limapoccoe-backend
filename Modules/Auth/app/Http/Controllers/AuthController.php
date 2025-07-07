<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\AuthUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use App\Helpers\FonnteHelper;
use App\Models\LogActivity;
use App\Http\Resources\AuthUserResource;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\LoginMasyarakatRequest;
use Modules\Auth\Http\Requests\LoginAdminRequest;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Http\Requests\VerifyRequest;
use Exception;






class AuthController extends Controller
{

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        // Rate Limiting untuk pengiriman OTP (ASVS 7.5.1 / SCP #94)
        $rateLimitKey = 'rl:register:wa:' . $validated['no_whatsapp'];
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60); // delay 60 detik antar OTP

        // Buat token registrasi dan OTP secara aman (ASVS 6.2.2 / SCP #104)
        $reg_token = Str::uuid()->toString();
        $otpCode = random_int(100000, 999999); // lebih aman daripada rand()
        $hashedOtp = Hash::make($otpCode); // ASVS 2.1.4 / SCP #30
        $expiredAt = now()->addMinutes(5); // Masa berlaku OTP

        // Data yang disimpan di Redis (tanpa menyimpan password mentah)
        $redisKey = 'otp:data:' . $reg_token;

        $data = [
            'name' => $validated['name'],
            'nik' => $validated['nik'],
            'no_whatsapp' => $validated['no_whatsapp'],
            'password_hash' => Hash::make($validated['password']), // simpan hanya hash
            'otp_hash' => $hashedOtp,
            'otp_expires_at' => $expiredAt->toDateTimeString(),
        ];

        try {
            Redis::setex($redisKey, 300, Crypt::encrypt(json_encode($data))); // ASVS 6.1.1 / SCP #132
        } catch (Exception $e) {
            Log::error('Redis OTP store error', ['error' => $e->getMessage(), 'whatsapp' => $validated['no_whatsapp']]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
            ], 500);
        }

        // Validasi apakah data Redis berhasil disimpan (ASVS 6.2.1)
        if (!Redis::exists($redisKey)) {
            return response()->json([
                'message' => 'Terjadi kesalahan penyimpanan data OTP. Silakan coba lagi.',
            ], 500);
        }

        // Kirim OTP via WhatsApp (ASVS 10.2.1 / SCP #143)
        $message = "Hai {$validated['name']} dengan NIK {$validated['nik']},\n\n"
            . "Kode OTP Anda adalah *$otpCode*\n\n"
            . "Kode ini berlaku selama 5 menit.\n"
            . "Jangan bagikan kode ini kepada siapapun.";

        $sent = FonnteHelper::sendWhatsAppMessage($validated['no_whatsapp'], $message);

        if (!$sent) {
            // Optional: Hapus Redis jika pengiriman gagal (data tidak berguna)
            Redis::del($redisKey);

            return response()->json([
                'message' => 'Kode OTP gagal dikirim ke WhatsApp. Silakan coba lagi.',
            ], 500);
        }

        // Logging aktivitas pengiriman OTP (ASVS 7.1.3 / SCP #127)
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => null,
            'activity_type' => 'otp_sent',
            'description' => 'Kode OTP dikirim via WhatsApp ke pengguna baru.',
            'ip_address' => $request->ip(),
            'metadata' => json_encode(['no_whatsapp' => $validated['no_whatsapp']]),
        ]);

        return response()->json([
            'message' => 'Kode OTP berhasil dikirim ke WhatsApp. Silakan cek pesan Anda.',
            'registration_token' => $reg_token,
        ], 200);
    }

    public function verifyOtp(VerifyRequest $request)
    {
        $validated = $request->validated();
        $token = $validated['registration_token'];
        $otpKey = 'otp:data:' . $token;
        $attemptKey = 'otp:attempt:' . $token;

        // ✅ Batasi brute-force OTP (ASVS 7.5.1 / SCP #94)
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            $seconds = RateLimiter::availableIn($attemptKey);
            return response()->json([
                'message' => 'Terlalu banyak percobaan OTP. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        $encryptedData = Redis::get($otpKey);

        if (!$encryptedData || !is_string($encryptedData)) {
            return response()->json([
                'message' => 'Token tidak ditemukan atau sudah kedaluwarsa.',
            ], 400);
        }

        try {
            $data = json_decode(Crypt::decrypt($encryptedData), true);

            // ✅ Validasi struktur data yang disimpan di Redis
            if (!isset($data['otp_hash'], $data['nik'], $data['no_whatsapp'], $data['name'], $data['password_hash'], $data['otp_expires_at'])) {
                return response()->json(['message' => 'Data OTP tidak valid.'], 400);
            }

            // ✅ Cek OTP
            if (!Hash::check($validated['otp_code'], $data['otp_hash'])) {
                RateLimiter::hit($attemptKey, 300); // Tambah percobaan selama 5 menit
                return response()->json(['message' => 'Kode OTP salah.'], 400);
            }

            // ✅ Cek apakah OTP sudah expired
            if (Carbon::parse($data['otp_expires_at'])->isPast()) {
                RateLimiter::hit($attemptKey, 300);
                return response()->json(['message' => 'Kode OTP telah kedaluwarsa.'], 400);
            }

            // ✅ Cek apakah user sudah ada
            $userExists = AuthUser::where('nik', $data['nik'])
                ->orWhere('no_whatsapp', $data['no_whatsapp'])
                ->exists();

            if ($userExists) {
                return response()->json([
                    'message' => 'User sudah terdaftar dengan data tersebut.',
                ], 409);
            }

            DB::beginTransaction();

            // ✅ Buat user baru
            $user = AuthUser::create([
                'name' => $data['name'],
                'nik' => $data['nik'],
                'no_whatsapp' => $data['no_whatsapp'],
                'password' => $data['password_hash'],
                'status' => 'active',
                'is_verified' => true,
            ]);

            $user->assignRole('masyarakat');

            // ✅ Generate JWT Token
            $jwt = JWTAuth::fromUser($user);

            // ✅ Catat aktivitas registrasi
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'register',
                'description' => 'User berhasil mendaftar dan diverifikasi.',
                'ip_address' => $request->ip(),
            ]);

            // ✅ Bersihkan Redis dan limiter
            Redis::del($otpKey);
            RateLimiter::clear($attemptKey);

            DB::commit();

            return response()->json([
                'message' => 'Registrasi berhasil. Selamat datang!',
                'user' => $user->only(['id', 'name', 'nik', 'no_whatsapp']),
                'access_token' => $jwt,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Verifikasi OTP gagal', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memverifikasi OTP.',
            ], 500);
        }
    }

    public function resendOtp(Request $request)
    {

        $request->validate([
            'registration_token' => 'required|string',
        ]);
        $encryptedData = Redis::get('otp:' . $request->registration_token);

        if (RateLimiter::tooManyAttempts('resend_otp:' . $request->registration_token, 1)) {
            $seconds = RateLimiter::availableIn('resend_otp:' . $request->registration_token);
            return response()->json([
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        if (!$encryptedData || !is_string($encryptedData)) {
            return response()->json([
                'message' => 'Token tidak ditemukan atau sudah kedaluwarsa.',
            ], 400);
        }

        try {
            
            $data = json_decode(Crypt::decrypt($encryptedData), true);

            if (!isset($data['no_whatsapp'], $data['name'], $data['nik'])) {
                return response()->json(['message' => 'Data OTP tidak valid.'], 400);
            }

            // Generate ulang kode OTP
            $otpCode = rand(100000, 999999);
            $hashedOtp = Hash::make($otpCode);
            $expiredAt = now()->addMinutes(5);

            // Update data di Redis
            $data['otp_code'] = $hashedOtp;
            $data['tgl_expire'] = $expiredAt;

            Redis::setex('otp:' . $request->registration_token, 300, Crypt::encrypt(json_encode($data)));

            // Kirim ulang OTP ke WhatsApp
            $message = "Hi {$data['name']} dengan NIK {$data['nik']},\n\n"
                . "Kode OTP Anda adalah *$otpCode*\n\n"
                . "Kode ini hanya berlaku 5 menit.\n"
                . "Jangan berikan kode ini kepada siapapun.";

            FonnteHelper::sendWhatsAppMessage($data['no_whatsapp'], $message);

            return response()->json([
                'message' => 'Kode OTP berhasil dikirim ulang ke WhatsApp.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengirim ulang OTP.',
                'error' => $e->getMessage(), // bisa dihapus di production
            ], 500);
        }
    }

    public function login(Request $request)
    {
        //Role Masyarakat
        if ($request->has('nik') && $request->has('password')) {
            return $this->loginMasyarakat($request);
        }

        // Role Internal (Staff Desa dan Kepala Desa)
        if ($request->has('username') && $request->has('password')) {
            return $this->loginInternal($request);
        }

    }

     public function loginMasyarakat(LoginMasyarakatRequest $request)
    {
        $validated = $request->validate([
            'nik' => 'required|digits:16',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).+$/',
        ], [
            'nik.required' => 'NIK harus diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'password.required' => 'Password harus diisi.',
            'password.min' => 'Password harus terdiri dari minimal 8 karakter.',
            'password.regex' => 'Password harus terdiri dari huruf besar, huruf kecil, dan angka.',
        ]);


        $user = AuthUser::where('nik', $validated['nik'])->whereHas('roles', fn($q) => $q->where('name', 'masyarakat'))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'NIK atau password salah. Silakan coba lagi.'], 401);
        }

        $token = JWTAuth::fromUser($user);

        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'login',
            'description' => 'User masyarakat berhasil login.',
            'ip_address' => $request->ip(),
        ]);


        return response()->json([
            'message' => 'Berhasil melakukan login.',
            'user' => $user->only(['id', 'name', 'nik', 'no_whatsapp']),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], 200);
    }

     public function loginInternal(LoginAdminRequest $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        // Ambil user berdasarkan username
        $user = AuthUser::where('username', $request->username)->first();

        // Cek user dan password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah.'
            ], 401);
        }

        // Pastikan user memiliki role staff atau kepala desa
        $allowedRoles = ['staff-desa', 'kepala-desa'];
        $role = $user->roles->pluck('name')->first();

        if (!in_array($role, $allowedRoles)) {
            return response()->json([
                'message' => 'Role tidak diizinkan untuk login di sini.'
            ], 403);
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login berhasil sebagai ' . $role,
            'user' => $user,
            'role' => $role,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function me(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['message' => 'User belum login. Silakan login terlebih dahulu'], 401);
            }

            return response()->json([
                'message' => 'Berhasil mendapatkan data user.',
                'user' => $user->only(['id', 'name', 'nik', 'no_whatsapp', 'roles']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mendapatkan data user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Ambil token dari header Authorization
            $token = JWTAuth::parseToken()->getToken();

            if (!$token) {
                return response()->json(['message' => 'Token tidak ditemukan.'], 400);
            }

            JWTAuth::invalidate($token); // Invalidate token supaya tidak bisa dipakai lagi

            return response()->json([
                'message' => 'Logout berhasil.'
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Gagal logout.',
                'error' => $e->getMessage()
            ], 500);
        }
    }







    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //

        return response()->json([]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //

        return response()->json([]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        //

        return response()->json([]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //

        return response()->json([]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //

        return response()->json([]);
    }
}
