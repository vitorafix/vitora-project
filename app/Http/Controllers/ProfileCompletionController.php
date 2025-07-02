<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address; // برای ذخیره آدرس‌ها
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileCompletionController extends Controller
{
    /**
     * نمایش فرم تکمیل پروفایل.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showCompletionForm(Request $request): View|RedirectResponse
    {
        // اطمینان حاصل می‌کنیم که کاربر لاگین کرده است.
        if (!Auth::check()) {
            return redirect()->route('auth.mobile-login-form');
        }

        $user = Auth::user();

        // اگر پروفایل کاربر از قبل کامل شده باشد، او را به صفحه اصلی هدایت می‌کنیم.
        // این کار از دسترسی مجدد به فرم تکمیل پروفایل جلوگیری می‌کند.
        if ($user->isProfileCompleted()) {
            return redirect()->intended('/')->with('status', 'پروفایل شما از قبل کامل است.');
        }

        // نمایش ویو 'profile.complete' و ارسال آبجکت کاربر به آن.
        // این فرم برای جمع‌آوری اطلاعات آدرس و تکمیل پروفایل استفاده می‌شود.
        return view('profile.complete', compact('user'));
    }

    /**
     * ذخیره اطلاعات تکمیل پروفایل و به‌روزرسانی وضعیت profile_completed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeCompletionForm(Request $request)
    {
        // اطمینان حاصل می‌کنیم که کاربر لاگین کرده است.
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'کاربر احراز هویت نشده است.'], 401);
            }
            return redirect()->route('auth.mobile-login-form');
        }

        $user = Auth::user();

        // اگر پروفایل کاربر از قبل کامل شده باشد، نیازی به پردازش مجدد نیست.
        if ($user->isProfileCompleted()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'پروفایل شما از قبل کامل است.'], 200);
            }
            return redirect()->intended('/')->with('status', 'پروفایل شما از قبل کامل است.');
        }

        // اعتبارسنجی ورودی‌های فرم تکمیل پروفایل.
        // فیلدهای آدرس، کد پستی، شماره تلفن (ثابت) و عنوان آدرس اعتبارسنجی می‌شوند.
        $validator = Validator::make($request->all(), [
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'digits:10'], // کد پستی 10 رقمی و اختیاری
            'phone_number' => ['nullable', 'string', 'regex:/^0[0-9]{10}$/', 'digits:11'], // تلفن ثابت 11 رقمی (با 0 شروع شود) و اختیاری
            'title' => ['nullable', 'string', 'max:255'], // عنوان آدرس (مثلاً: خانه، محل کار) و اختیاری
        ]);

        // در صورت عدم موفقیت در اعتبارسنجی، خطاها را برمی‌گرداند.
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // ذخیره آدرس جدید برای کاربر در جدول 'addresses'.
        // فرض می‌کنیم در این مرحله، کاربر حداقل یک آدرس را وارد می‌کند.
        $address = new Address();
        $address->user_id = $user->id; // ارتباط آدرس با کاربر لاگین شده
        $address->title = $request->input('title', 'آدرس اصلی'); // اگر عنوان وارد نشود، 'آدرس اصلی' به عنوان پیش‌فرض تنظیم می‌شود.
        $address->province = $request->province;
        $address->city = $request->city;
        $address->address = $request->address;
        $address->postal_code = $request->postal_code;
        $address->phone_number = $request->phone_number;
        $address->is_default = true; // اولین آدرس وارد شده به عنوان آدرس پیش‌فرض تنظیم می‌شود.
        $address->save();

        // به‌روزرسانی وضعیت profile_completed کاربر به true.
        // این نشان می‌دهد که کاربر اطلاعات پروفایل ضروری خود را تکمیل کرده است.
        $user->profile_completed = true;
        $user->save();

        // پاسخ JSON برای درخواست‌های AJAX.
        if ($request->expectsJson()) {
            return response()->json(['message' => 'پروفایل با موفقیت تکمیل شد.', 'user' => $user], 200);
        }

        // هدایت کاربر به مقصدی که قبل از هدایت به تکمیل پروفایل قصد داشت به آن برود،
        // یا به صفحه اصلی اگر مقصد قبلی وجود نداشته باشد.
        return redirect()->intended('/')->with('status', 'پروفایل شما با موفقیت تکمیل شد.');
    }
}

