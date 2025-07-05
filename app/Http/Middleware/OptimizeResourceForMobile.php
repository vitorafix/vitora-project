<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class OptimizeResourceForMobile
{
    /**
     * Handle an incoming request.
     * درخواست ورودی را مدیریت می‌کند.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // منطق تشخیص دستگاه موبایل
        $isMobile = false;
        $userAgent = $request->header('User-Agent');

        // بهبود: روش 1 - تشخیص بر اساس مسیر API (اولویت بالاتر برای دقت)
        // اگر مسیر API موبایل ثابت و مشخص است، این روش سریع‌تر و دقیق‌تر است.
        // مثال: اگر تمام API های موبایل شما با /api/mobile/ شروع می‌شوند
        if ($request->is('api/mobile/*') || $request->is('mobile/api/*')) {
            $isMobile = true;
        }
        // بهبود: اگر مسیر API موبایل نبود، به سراغ User-Agent می‌رویم
        else {
            // بهبود: User-Agent regex از فایل کانفیگ خوانده می‌شود
            $mobileUserAgentRegex = config('mobile_detection.user_agent_regex.mobile');
            $mobileUserAgentSubstrRegex = config('mobile_detection.user_agent_regex.mobile_substr');

            if ($userAgent && (
                preg_match($mobileUserAgentRegex, $userAgent) ||
                preg_match($mobileUserAgentSubstrRegex, substr($userAgent, 0, 4))
            )) {
                $isMobile = true;
            }
            // نکته: برای تشخیص‌های پیشرفته‌تر و به‌روزتر، می‌توانید از پکیج‌های ثالث
            // مانند 'jenssegers/agent' استفاده کنید.
        }

        // بهبود: لاگ کردن وضعیت تشخیص فقط در محیط توسعه یا با هدر دیباگ
        if (app()->environment('local') || $request->hasHeader('X-Debug-Mobile')) {
            Log::info('Mobile detection middleware executed', [
                'is_mobile_request' => $isMobile,
                'user_agent' => $userAgent,
                'request_path' => $request->path()
            ]);
        }

        // تنظیم فلگ در Service Container
        // این فلگ توسط متد isMobileRequest() در CartResource خوانده می‌شود.
        app()->instance('isMobileRequest', $isMobile);

        return $next($request);
    }
}
