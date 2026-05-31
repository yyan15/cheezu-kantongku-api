<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Memverifikasi ID Token Google dari Android dan login/register user.
     */
    public function verifyGoogle(Request $request)
    {
        // 1. Validasi input request
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $idToken = $request->id_token;
            Log::debug('Google Login Attempt. Token: ' . substr($idToken, 0, 20) . '...');

            // Gunakan GOOGLE_ANDROID_CLIENT_ID atau GOOGLE_CLIENT_ID dari .env
            $clientId = env('GOOGLE_ANDROID_CLIENT_ID') ?? env('GOOGLE_CLIENT_ID');

            if (!$clientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server Error: GOOGLE_CLIENT_ID belum diatur di .env'
                ], 500);
            }

            // 2. Inisialisasi Google Client
            $client = new GoogleClient(['client_id' => $clientId]);

            $payload = $client->verifyIdToken($idToken);

            if ($payload) {
                $email = $payload['email'];
                $name  = $payload['name'] ?? 'User';
                $ssoId = $payload['sub'];

                // 3. Cari user berdasarkan email (lebih aman untuk menghindari duplicate email)
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Update data SSO jika belum ada atau berubah
                    $user->update([
                        'sso_provider' => 'google',
                        'sso_id'       => $ssoId,
                        'name'         => $name, // Update nama dari Google (opsional)
                    ]);
                } else {
                    // Buat user baru jika email belum terdaftar
                    $user = User::create([
                        'name'         => $name,
                        'email'        => $email,
                        'sso_provider' => 'google',
                        'sso_id'       => $ssoId,
                        'password'     => null, // Password kosong karena login via SSO
                    ]);
                }

                // 4. Buat token Sanctum
                $user->tokens()->delete(); // Hapus token lama agar hanya 1 sesi aktif (opsional)
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'user'  => $user
                    ]
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'ID Token tidak valid'
                ], 401);
            }

        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }
}
