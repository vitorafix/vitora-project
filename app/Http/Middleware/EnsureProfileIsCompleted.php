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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
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
            // نام روت صفحه تکمیل پروفایل: 'profile.completion.form' (مطابق کنترلر)
            if (!$user->isProfileCompleted() && $request->route()->getName() !== 'profile.completion.form') {
                // ذخیره مقصد فعلی کاربر در سشن، تا پس از تکمیل پروفایل به آنجا بازگردد.
                session()->put('url.intended', $request->url());

                // اگر درخواست AJAX یا درخواست با انتظار JSON باشد، پاسخ JSON می‌دهیم.
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => 'لطفاً ابتدا پروفایل خود را تکمیل کنید.'], 403);
                }

                // کاربر را به صفحه تکمیل پروفایل هدایت می‌کنیم.
                return redirect()->route('profile.completion.form');
            }
        }

        // اگر پروفایل کامل بود یا کاربر در حال تکمیل پروفایل بود، اجازه ادامه درخواست را می‌دهیم.
        return $next($request);
    }
}
