<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use App\Models\User; // مطمئن شوید مدل User شما وجود دارد
use Illuminate\Support\Facades\Auth;

class JwtMiddleware
{
    private $jwtSecret;

    public function __construct()
    {
        $this->jwtSecret = env('JWT_SECRET', 'your_super_secret_jwt_key');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // توکن را از کوکی دریافت کنید
        // 'jwt_token' نام کوکی است که در AuthController تنظیم شده است
        $token = $request->cookie('jwt_token');

        if (!$token) {
            // اگر توکن نبود، ممکن است کاربر وارد نشده باشد یا توکن منقضی شده باشد
            // اجازه دهید درخواست ادامه پیدا کند، اما کاربر احراز هویت نشده است
            return $next($request);
        }

        try {
            $credentials = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        } catch (ExpiredException $e) {
            // اگر توکن منقضی شده، کوکی را حذف کنید تا کاربر مجبور به ورود مجدد شود
            return response()->json(['message' => 'توکن منقضی شده است.'], 401)
                             ->cookie('jwt_token', '', 0, '/', null, false, true);
        } catch (SignatureInvalidException $e) {
            return response()->json(['message' => 'امضای توکن نامعتبر است.'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'توکن نامعتبر است.'], 401);
        }

        // پیدا کردن کاربر بر اساس 'sub' (شناسه کاربر) در Payload
        $user = User::find($credentials->sub);

        if (!$user) {
            return response()->json(['message' => 'کاربر یافت نشد.'], 401);
        }

        // قرار دادن کاربر در درخواست تا در کنترلرها قابل دسترسی باشد
        // این کار به auth()->user() اجازه می‌دهد تا کاربر را برگرداند
        Auth::login($user);

        return $next($request);
    }
}