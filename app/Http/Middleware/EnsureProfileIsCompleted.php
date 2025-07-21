<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log; // Added for logging

class EnsureProfileIsCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // بررسی می‌کنیم که آیا کاربر احراز هویت شده است یا خیر.
        // Auth::guard('api')->check() برای اطمینان از بررسی گارد JWT
        if (Auth::guard('api')->check()) { // فرض می‌کنیم گارد API شما برای JWT است
            $user = Auth::guard('api')->user(); // دریافت کاربر احراز هویت شده توسط JWT

            // اگر کاربر وجود دارد و پروفایلش تکمیل نشده است
            if ($user && !$user->isProfileCompleted()) {
                Log::info('EnsureProfileIsCompleted: User profile not completed for user ID: ' . $user->id);

                // اگر درخواست AJAX یا درخواست با انتظار JSON باشد، پاسخ JSON می‌دهیم.
                // این برای APIها مناسب است.
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => 'لطفاً ابتدا پروفایل خود را تکمیل کنید.'], 403);
                }

                // برای درخواست‌های وب (اگر هنوز دارید و نیاز به ریدایرکت دارند)
                // توجه: در یک سیستم کاملاً JWT، ریدایرکت‌ها معمولاً توسط فرانت‌اند مدیریت می‌شوند.
                // این بخش ممکن است در آینده حذف شود اگر وب‌سایت نیز کاملاً SPA و JWT محور شود.
                Log::warning('EnsureProfileIsCompleted: Web request for user ' . $user->id . ' needs profile completion. Redirecting.');
                return redirect()->route('profile.completion.form')->with('status', 'لطفاً ابتدا پروفایل خود را تکمیل کنید.');
            }
        } else {
            // اگر کاربر احراز هویت نشده است، اجازه می‌دهیم میدل‌ویرهای بعدی (مانند Authenticate) مدیریت کنند.
            Log::debug('EnsureProfileIsCompleted: User not authenticated. Skipping profile completion check.');
        }

        // اگر پروفایل کامل بود یا کاربر احراز هویت نشده بود (و توسط میدل‌ویرهای دیگر مدیریت می‌شود)، اجازه ادامه درخواست را می‌دهیم.
        return $next($request);
    }
}
