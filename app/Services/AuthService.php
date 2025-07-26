<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Jobs\ProcessAnalyticsEvents; // 🟢 جدید: ایمپورت Job آنالیتیکس
use Carbon\Carbon; // 🟢 جدید: ایمپورت Carbon برای timestamp

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

        // 🟢 جدید: ارسال Job آنالیتیکس برای رویداد ورود کاربر
        // این Job به صف اضافه می‌شود و توسط Queue Worker پردازش خواهد شد.
        ProcessAnalyticsEvents::dispatch([
            [
                'user_id' => $user->id,
                'guest_uuid' => request()->cookie('guest_uuid') ?? (string) \Illuminate\Support\Str::uuid(), // از کوکی یا UUID جدید استفاده کنید
                'eventName' => '[INT]_user_login',
                'eventData' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                ],
                'screenData' => [], // اطلاعات صفحه در زمان لاگین (اگر نیاز دارید)
                'trafficSource' => null,
                'screenViews' => 0,
                'screenTime' => 0,
                'sessionTime' => 0,
                'currentUrl' => request()->fullUrl(),
                'pageTitle' => 'User Login',
                'scrollDepth' => 0,
                'deviceInfo' => [], // اطلاعات دستگاه (اگر نیاز دارید)
                'performanceMetrics' => [],
                'interactionDetails' => [],
                'searchQuery' => null,
                'timestamp' => Carbon::now()->toIso8601String(), // زمان دقیق رویداد
            ]
        ])->onQueue('default'); // Job را به صف 'default' ارسال می‌کند

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
