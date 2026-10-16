<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Biodata;
use App\Models\Berkas;
use App\Models\PendaftaranSpmb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function registerSendOtp(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tb_users,email',
            'username' => 'required|string|max:100|unique:tb_users,username',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:siswa,panitia,admin',
        ]);

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $role = $request->input('role', 'siswa');

        $registrationData = [
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $role,
            'otp' => $otp,
        ];

        Cache::put('register_' . $request->email, $registrationData, now()->addMinutes(5));

        $messageContent = "Berikut adalah kode OTP Anda untuk verifikasi akun Anda di SMK TI Bali Global:\n\n"
            . "Kode OTP: {$otp}\n\n"
            . "Kode OTP ini bersifat rahasia dan hanya dapat digunakan satu kali. Demi keamanan akun Anda, jangan membagikan kode OTP ini kepada siapa pun.\n\n"
            . "Hormat kami,\n"
            . "SMK TI Bali Global";

        try {
            Mail::raw($messageContent, function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Kode OTP Verifikasi Registrasi - SMK TI Bali Global');
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim email OTP: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP registrasi berhasil dikirim ke email',
        ], 200);
    }

    public function registerVerify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $cachedData = Cache::get('register_' . $request->email);

        if (!$cachedData || $cachedData['otp'] !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP salah atau sudah kadaluwarsa.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $role = $cachedData['role'] ?? 'siswa';
            $idPanitia = null;

            if (in_array($role, ['panitia', 'admin'])) {
                $year = date('Y');
                $latestPanitia = User::whereNotNull('id_panitia')
                    ->where('id_panitia', 'LIKE', "PNT-{$year}-%")
                    ->orderBy('id_user', 'desc')
                    ->first();

                if ($latestPanitia) {
                    $lastNumber = (int) substr($latestPanitia->id_panitia, -3);
                    $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $nextNumber = '001';
                }

                $idPanitia = "PNT-{$year}-{$nextNumber}";
            }

            $user = User::create([
                'email' => $cachedData['email'],
                'username' => $cachedData['username'],
                'password' => $cachedData['password'],
                'role' => $role,
                'id_panitia' => $idPanitia,
            ]);

            // if ($role === 'siswa') {
            //     $biodata = Biodata::create([
            //         'id_user' => $user->id_user,
            //         'nama_lengkap' => $cachedData['nama_lengkap'],
            //     ]);

            //     $berkas = Berkas::create([
            //         'id_user' => $user->id_user,
            //         'status_verifikasi' => 'belum_diunggah',
            //     ]);

            //     PendaftaranSpmb::create([
            //         'id_user' => $user->id_user,
            //         'id_biodata' => $biodata->id_biodata,
            //         'id_berkas' => $berkas->id_berkas,
            //         'no_pendaftaran' => 'SPMB-' . date('Ymd') . '-' . str_pad($user->id_user, 4, '0', STR_PAD_LEFT),
            //         'status_kelulusan' => 'proses',
            //     ]);
            // }

            DB::commit();
            Cache::forget('register_' . $request->email);

            $token = $user->createToken('auth_token', [$user->role], now()->addWeeks(2))->plainTextToken;
            $cookie = cookie('auth_token', $token, 14 * 24 * 60, null, null, true, true);

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi dan verifikasi email berhasil',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addWeeks(2)->toIso8601String(),
                'data' => [
                    'id_user' => $user->id_user,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'id_panitia' => $user->id_panitia,
                ]
            ], 201)->withCookie($cookie);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Registrasi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::lower($request->input('login')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 6)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'status' => 'error',
                'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        $loginInput = $request->login;
        $user = User::where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->orWhere('id_panitia', $loginInput)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            $attemptsLeft = RateLimiter::retriesLeft($throttleKey, 6);

            return response()->json([
                'status' => 'error',
                'message' => 'Kredensial login tidak valid. Sisa percobaan: ' . $attemptsLeft,
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken('auth_token', [$user->role], now()->addWeeks(2))->plainTextToken;
        $cookie = cookie('auth_token', $token, 14 * 24 * 60, null, null, true, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addWeeks(2)->toIso8601String(),
            'data' => [
                'id_user' => $user->id_user,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'id_panitia' => $user->id_panitia,
            ]
        ], 200)->withCookie($cookie);
    }

    public function forgotPasswordSendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:tb_users,email',
        ]);

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        Cache::put('otp_reset_' . $request->email, $otp, now()->addMinutes(5));

        $messageContent = "Berikut adalah kode OTP Anda untuk reset password di SMK TI Bali Global:\n\n"
            . "Kode OTP: {$otp}\n\n"
            . "Kode OTP ini bersifat rahasia.";

        try {
            Mail::raw($messageContent, function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Kode OTP Reset Password - SMK TI Bali Global');
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim email OTP: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP reset password berhasil dikirim ke email',
        ], 200);
    }

    public function verifyForgotOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:tb_users,email',
            'otp' => 'required|string',
        ]);

        $cachedOtp = Cache::get('otp_reset_' . $request->email);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP salah atau sudah kadaluwarsa.',
            ], 400);
        }

        $user = User::with('biodata')->where('email', $request->email)->first();
        $namaUser = $user->biodata->nama_lengkap ?? $user->username;

        return response()->json([
            'status' => 'success',
            'message' => 'Verifikasi OTP berhasil',
            'data' => [
                'nama_user' => $namaUser,
                'email' => $user->email
            ]
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:tb_users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $cachedOtp = Cache::get('otp_reset_' . $request->email);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP salah atau verifikasi telah kadaluwarsa.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        Cache::forget('otp_reset_' . $request->email);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diperbarui. Silakan login kembali.',
        ], 200);
    }

    public function me(Request $request)
    {
        $user = User::with([
            'biodata',
            'berkas',
            'pendaftaran.jurusan1',
            'pendaftaran.jurusan2',
            'pendaftaran.jurusanDiterima'
        ])->find($request->user()->id_user);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }
        $cookie = cookie()->forget('auth_token');

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout'
        ], 200)->withCookie($cookie);
    }
}