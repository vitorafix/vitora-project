<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private $jwtSecret;

    public function __construct()
    {
        $this->jwtSecret = env('JWT_SECRET', 'your_super_secret_jwt_key');
    }

    public function login(string $email, string $password): string
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('اطلاعات کاربری نامعتبر است.');
        }

        $payload = [
            'iss' => url('/api/auth/login'),
            'iat' => time(),
            'exp' => time() + (60 * 60), // 1 hour expiration
            'sub' => $user->id,
            'prv' => sha1($user->password),
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function logout(): void
    {
        // In a JWT-based system, logout primarily involves removing the token from the client.
        // Server-side invalidation might involve a blacklist, but for simplicity here,
        // we rely on the client removing the HttpOnly cookie.
        // The JwtMiddleware will handle expired/invalid tokens automatically.
    }

    // This method is not directly used by AuthController anymore,
    // as JwtMiddleware directly attaches the user to the request.
    // However, if you needed to retrieve user data based on a token
    // within the service layer, you could do it here.
    public function getUserFromToken(string $token): ?User
    {
        try {
            $credentials = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return User::find($credentials->sub);
        } catch (\Exception $e) {
            return null;
        }
    }
}