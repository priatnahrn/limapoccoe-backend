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




class AuthController extends Controller
{
    
     public function register(Request $request)
    {
        // Input Validation
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'nik' => 'required|digits:16|unique:auth_users,nik',
            'no_whatsapp' => 'required|string|min:11|unique:auth_users,no_whatsapp',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
        ], [
            'name.required' => 'Nama lengkap harus diisi. Pastikan Anda mengisi nama lengkap dengan benar.',
            'name.min' => 'Nama harus terdiri dari minimal 3 karakter.',
            'name.max' => 'Nama harus terdiri dari maksimal 255 karakter.',
            'nik.required' => 'NIK harus diisi. Pastikan NIK Anda sudah benar.',
            'nik.unique' => 'NIK sudah terdaftar. Mohon gunakan NIK lain.',
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'no_whatsapp.required' => 'Nomor WhatsApp harus diisi. Pastikan Anda memasukkan nomor WhatsApp yang benar.',
            'no_whatsapp.min' => 'Nomor WhatsApp harus terdiri dari minimal 11 angka.',
            'no_whatsapp.unique' => 'Nomor WhatsApp sudah terdaftar. Mohon gunakan nomor WhatsApp lain.',
            'password.required' => 'Password harus diisi. Pastikan Anda memasukkan password yang benar.',
            'password.min' => 'Password harus terdiri dari minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok. Pastikan Anda memasukkan password yang sama.',
            'password.regex' => 'Password harus terdiri dari minimal 8 karakter dan mengandung setidaknya satu huruf besar, satu huruf kecil, satu angka, dan satu karakter khusus.',
        ]);

        // Rate Limitter
        $key = 'otp:' . $validated['no_whatsapp'];

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $reg_token = Str::uuid();
        $otpCode = rand(100000, 999999);
        $hashedOtp = Hash::make($otpCode);
        $expiredAt = now()->addMinutes(5);

        // Storage Management to Redis
        $data = [
            'name' => $validated['name'],
            'nik' => $validated['nik'],
            'no_whatsapp' => $validated['no_whatsapp'],
            'password' => Hash::make($validated['password']),
            'otp_code' => $hashedOtp,
            'tgl_expire' => $expiredAt,
        ];

        Redis::setex('otp:' . $reg_token, 300, Crypt::encrypt(json_encode($data)));
    

        if (!Redis::exists('otp:' . $reg_token)) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
            ], 500);
        }

        // Send OTP to WhatsApp
        $message = "Hi {$validated['name']} dengan NIK {$validated['nik']},\n\n"
            . "Kode OTP Anda adalah *$otpCode*\n\n"
            . "Kode ini hanya berlaku 5 menit.\n"
            . "Jangan berikan kode ini kepada siapapun.";

        $sent = FonnteHelper::sendWhatsAppMessage($validated['no_whatsapp'], $message);

        if (!$sent) {
            return response()->json([
                'message' => 'Kode OTP gagal dikirim ke WhatsApp. Silakan coba lagi.',
            ], 500);
        }

        // Activity Log Management
        LogActivity::create([
            'id' => Str::uuid(),
            'user_id' => null,
            'activity_type' => 'kirim_otp',
            'description' => 'Kode OTP baru telah dikirim ke WhatsApp.',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Kode OTP berhasil dikirim ke WhatsApp. Silakan cek WhatsApp Anda.',
            'registration_token' => $reg_token,
        ], 200);
    }

   public function verifyOtp(Request $request)
    {
        $request->validate([
            'registration_token' => 'required|string',
            'otp_code' => 'required|digits:6',
        ], [
            'registration_token.required' => 'Token OTP harus diisi.',
            'registration_token.string' => 'Token OTP harus berupa string.',
            'otp_code.required' => 'Kode OTP harus diisi.',
            'otp_code.digits' => 'Kode OTP harus terdiri dari 6 angka.',
        ]);

        // Ambil data OTP dari Redis
        $encryptedData = Redis::get('otp:' . $request->registration_token);

        if (!$encryptedData || !is_string($encryptedData)) {
            return response()->json([
                'message' => 'Token tidak ditemukan atau sudah kedaluwarsa.',
            ], 400);
        }

        try {
            $data = json_decode(Crypt::decrypt($encryptedData), true);

            if (!isset($data['otp_code'], $data['nik'], $data['no_whatsapp'], $data['name'], $data['password'], $data['tgl_expire'])) {
                return response()->json(['message' => 'Data OTP tidak valid.'], 400);
            }

            if (!Hash::check($request->otp_code, $data['otp_code'])) {
                return response()->json(['message' => 'Kode OTP salah. Silakan coba lagi.'], 400);
            }

            if (Carbon::parse($data['tgl_expire'])->isPast()) {
                return response()->json(['message' => 'Kode OTP telah kadaluwarsa.'], 400);
            }

            $userExists = AuthUser::where('nik', $data['nik'])
                ->orWhere('no_whatsapp', $data['no_whatsapp'])
                ->exists();

            if ($userExists) {
                return response()->json([
                    'message' => 'User sudah terdaftar dengan data tersebut. Silakan gunakan data lain.',
                ], 409);
            }

            DB::beginTransaction();

            $user = AuthUser::create([
                'name' => $data['name'],
                'nik' => $data['nik'],
                'no_whatsapp' => $data['no_whatsapp'],
                'password' => $data['password'], // Hash::make(...) jika belum di-hash
                'status' => 'active',
                'is_verified' => true,
            ]);

            $user->assignRole('masyarakat');

            $token = JWTAuth::fromUser($user);

            LogActivity::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'activity_type' => 'register',
                'description' => 'User baru telah terdaftar.',
                'ip_address' => $request->ip(),
            ]);

            Redis::del('otp:' . $request->registration_token);

            DB::commit();

            return response()->json([
                'message' => 'Registrasi dan login berhasil. Lengkapi profil sekarang untuk mengakses fitur',
                'user' => $user->only(['id', 'name', 'nik', 'no_whatsapp']),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => (int) JWTAuth::factory()->getTTL() * 60,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat memverifikasi OTP.',
                'error' => $e->getMessage(), // bisa dihapus di production
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

     public function loginMasyarakat(Request $request)
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

     public function loginInternal(Request $request)
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
