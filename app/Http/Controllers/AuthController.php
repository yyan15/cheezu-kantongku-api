<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Memverifikasi ID Token Google dari Android dan login/register user.
     */
    public function verifyGoogleToken(Request $request)
    {
        // 1. Validasi input request dari Android
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $idToken = $request->id_token;
            $clientId = env('GOOGLE_ANDROID_CLIENT_ID');

            // 2. Inisialisasi Google Client untuk verifikasi token
            $client = new GoogleClient(['client_id' => $clientId]);

            // Verifikasi token secara internal (mengecek signature, expiration, dan client_id)
            $payload = $client->verifyIdToken($idToken);

            if ($payload) {
                // Jika token valid, Google akan mengembalikan data user:
                $ssoId = $payload['sub']; // Ini adalah sso_id (ID unik user di Google)
                $email = $payload['email'];
                $name  = $payload['name'] ?? 'User';

                // 3. Cocokkan ke database (Just-In-Time Provisioning)
                // Jika sso_id belum ada, buat baru. Jika sudah ada, ambil datanya.
                $user = User::updateOrCreate([
                    'sso_provider' => 'google',
                    'sso_id'       => $ssoId,
                ], [
                    'name'         => $name,
                    'email'        => $email,
                    'password'     => null, // Kosong karena login via SSO
                ]);

                // 4. Buat token internal aplikasi (Laravel Sanctum) untuk Android
                $token = $user->createToken('android_api_token')->plainTextToken;

                // 5. Kirim response balik ke Android
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Otentikasi Google berhasil',
                    'data'    => [
                        'token' => $token,
                        'user'  => $user
                    ]
                ], 200);

            } else {
                // Jika token palsu atau sudah expired
                return response()->json([
                    'status'  => 'error',
                    'message' => 'ID Token tidak valid atau telah kedaluwarsa'
                ], 401);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus token akses user (Logout).
     */
    public function logout(Request $request)
    {
        try {
            // Menghapus token yang saat ini digunakan untuk me-request API ini
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Logout berhasil, token telah dihapus dari database.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal melakukan logout: ' . $e->getMessage()
            ], 500);
        }
    }
}
