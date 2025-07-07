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
use Modules\Auth\Http\Requests\ResendOtpRequest;
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

    public function resendOtp(ResendOtpRequest $request)
    {
        // ✅ ASVS 5.1.1 – Validasi input
        $validated = $request->validated();

        $token = $request->registration_token;
        $redisKey = 'otp:data:' . $token;
        $rateLimitKey = 'resend_otp:' . $token;

        // ✅ ASVS 7.5.1 / SCP #94 – Rate limiting agar tidak spam OTP
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60); // 1x kirim per 60 detik

        // ✅ Ambil data dari Redis
        $encryptedData = Redis::get($redisKey);
        if (!$encryptedData || !is_string($encryptedData)) {
            return response()->json([
                'message' => 'Token tidak ditemukan atau sudah kedaluwarsa.',
            ], 400);
        }

        try {
            $data = json_decode(Crypt::decrypt($encryptedData), true);

            // ✅ Validasi isi Redis (ASVS 5.1.3)
            if (!isset($data['no_whatsapp'], $data['name'], $data['nik'])) {
                return response()->json(['message' => 'Data OTP tidak valid.'], 400);
            }

            // ✅ Buat ulang OTP yang aman
            $otpCode = random_int(100000, 999999); // ASVS 2.1.1 / SCP #104
            $hashedOtp = Hash::make($otpCode);     // ASVS 2.1.4 / SCP #30
            $expiredAt = now()->addMinutes(5);

            // ✅ Perbarui Redis
            $data['otp_hash'] = $hashedOtp;
            $data['otp_expires_at'] = $expiredAt->toDateTimeString();

            Redis::setex($redisKey, 300, Crypt::encrypt(json_encode($data))); // ASVS 6.1.1

            // ✅ Kirim OTP ke WhatsApp (ASVS 10.2.1 / SCP #143)
            $message = "Hai {$data['name']} dengan NIK {$data['nik']},\n\n"
                . "Kode OTP Anda adalah *$otpCode*\n\n"
                . "Kode ini berlaku selama 5 menit.\n"
                . "Jangan bagikan kode ini kepada siapa pun.";

            $sent = FonnteHelper::sendWhatsAppMessage($data['no_whatsapp'], $message);

            if (!$sent) {
                return response()->json([
                    'message' => 'Kode OTP gagal dikirim ke WhatsApp.',
                ], 500);
            }

            // ✅ Log aktivitas OTP resend (ASVS 7.1.3 / SCP #127)
            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => null,
                'activity_type' => 'otp_resend',
                'description' => 'Kode OTP dikirim ulang via WhatsApp.',
                'ip_address' => $request->ip(),
                'metadata' => json_encode(['no_whatsapp' => $data['no_whatsapp']]),
            ]);

            return response()->json([
                'message' => 'Kode OTP berhasil dikirim ulang ke WhatsApp.',
            ], 200);

        } catch (Throwable $e) {
            Log::error('Resend OTP error', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengirim ulang OTP.',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        if ($request->has('nik') && $request->has('password')) {
            return $this->loginMasyarakat($request);
        }

        if ($request->has('username') && $request->has('password')) {
            return $this->loginInternal($request);
        }
    }

    private function loginMasyarakat(LoginMasyarakatRequest $request)
    {
        $validated = $request->validated();
        $rateKey = 'login:masyarakat:' . $validated['nik'];

        // ✅ ASVS 7.5.1 – Rate limiting untuk brute-force login
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return response()->json([
                'message' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        $user = AuthUser::where('nik', $validated['nik'])
            ->whereHas('roles', fn($q) => $q->where('name', 'masyarakat'))
            ->first();

        // ✅ ASVS 2.1.4 – Password hash check
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($rateKey, 60); // Hit limit untuk brute-force protection
            return response()->json([
                'message' => 'NIK atau password salah. Silakan coba lagi.',
            ], 401);
        }

        RateLimiter::clear($rateKey); // Clear jika login sukses

        $token = JWTAuth::fromUser($user);

        // ✅ ASVS 7.1.3 – Logging aktivitas keamanan
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
        ]);
    }

    private function loginInternal(LoginAdminRequest $request)
    {
        $validated = $request->validated();
        $rateKey = 'login:internal:' . $validated['username'];

        // ✅ ASVS 7.5.1 – Rate limiting untuk brute-force login
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return response()->json([
                'message' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        $user = AuthUser::where('username', $validated['username'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($rateKey, 60); // Delay brute-force
            return response()->json([
                'message' => 'Username atau password salah.',
            ], 401);
        }

        // ✅ ASVS 5.1.6 – Cek role apakah diizinkan
        $allowedRoles = ['staff-desa', 'kepala-desa'];
        $role = $user->roles->pluck('name')->first();

        if (!in_array($role, $allowedRoles)) {
            return response()->json([
                'message' => 'Role tidak diizinkan untuk login di sini.',
            ], 403);
        }

        RateLimiter::clear($rateKey); // Login sukses → bersihkan limit

        $token = JWTAuth::fromUser($user);

        // ✅ Logging login internal
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'activity_type' => 'login',
            'description' => 'User internal berhasil login sebagai ' . $role . '.',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Login berhasil sebagai ' . $role,
            'user' => $user->only(['id', 'name', 'username', 'email']),
            'role' => $role,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function me()
    {
        try {
            // ✅ Autentikasi user dari token (ASVS 2.1.1)
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan atau belum login.'
                ], 401);
            }

            // ✅ Ambil role name (tanpa expose relasi penuh)
            $roles = $user->roles->pluck('name');

            return response()->json([
                'message' => 'Berhasil mendapatkan data user.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nik' => $user->nik,
                    'no_whatsapp' => $user->no_whatsapp,
                    'roles' => $roles,
                ],
            ], 200);

        // ✅ Tangani berbagai exception JWT (ASVS 2.1.1 / SCP #107)
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token telah kedaluwarsa. Silakan login kembali.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token tidak valid.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token tidak ditemukan.'], 401);
        } catch (\Throwable $e) {
            Log::error('Gagal mendapatkan data user', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // ✅ Ambil token dari header Authorization (ASVS 2.1.1)
            $token = JWTAuth::parseToken()->getToken();

            if (!$token) {
                return response()->json([
                    'message' => 'Token tidak ditemukan dalam permintaan.',
                ], 400);
            }

            // ✅ Invalidate token agar tidak bisa dipakai lagi (ASVS 2.2.4)
            JWTAuth::invalidate($token);

            // ✅ Logging aktivitas logout (ASVS 7.1.3 / SCP #127)
            $user = JWTAuth::authenticate($token);
            if ($user) {
                LogActivity::create([
                    'id' => Str::uuid(),
                    'user_id' => $user->id,
                    'activity_type' => 'logout',
                    'description' => 'User melakukan logout.',
                    'ip_address' => $request->ip(),
                ]);
            }

            return response()->json([
                'message' => 'Logout berhasil.',
            ], 200);

        } catch (JWTException $e) {
            Log::warning('Logout error', ['error' => $e->getMessage()]); // 🧾 Log internal
            return response()->json([
                'message' => 'Gagal logout karena token tidak valid atau sudah kadaluwarsa.',
            ], 401);
        } catch (\Throwable $e) {
            Log::error('Logout exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat logout.',
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
