<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // بررسی می‌کنیم که آیا کاربر احراز هویت شده است یا خیر.
        // اگر کاربر مهمان باشد، میدل‌ور auth او را به صفحه ورود هدایت می‌کند،
        // بنابراین این میدل‌ور فقط برای کاربران لاگین شده اجرا می‌شود.
        if (Auth::check()) {
            $user = Auth::user();

            // بررسی می‌کنیم که آیا فیلد profile_completed کاربر false است.
            // و همچنین مطمئن می‌شویم که کاربر در حال حاضر در مسیر تکمیل پروفایل نباشد،
            // تا از حلقه بی‌نهایت ریدایرکت جلوگیری شود.
            if (!$user->isProfileCompleted() && $request->route()->getName() !== 'profile.complete') {
                // ذخیره مقصد فعلی کاربر در سشن، تا پس از تکمیل پروفایل به آنجا بازگردد.
                session()->put('url.intended', $request->url());

                // کاربر را به صفحه تکمیل پروفایل هدایت می‌کنیم.
                return redirect()->route('profile.complete');
            }
        }

        // اگر پروفایل کامل بود یا کاربر در حال تکمیل پروفایل بود، اجازه ادامه درخواست را می‌دهیم.
        return $next($request);
    }
}

