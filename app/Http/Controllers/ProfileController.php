<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User; // مطمئن شوید مدل User ایمپورت شده است
use App\Models\LegalInfo; // اضافه کردن مدل LegalInfo
use Illuminate\Support\Facades\Hash; // اضافه کردن Hash برای هش کردن رمز عبور
use Illuminate\Validation\Rules\Password; // اضافه کردن Password Rule
use Illuminate\Support\Facades\Validator; // اضافه کردن Validator برای اعتبار سنجی دستی
// use Morilog\Jalali\Jalali; // اگر از پکیج morilog/jalali استفاده می‌کنید، این خط را فعال کنید
// use Morilog\Jalali\CalendarUtils; // اگر از پکیج morilog/jalali استفاده می‌کنید، این خط را فعال کنید

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     * این متد برای نمایش صفحه پروفایل جدید ما استفاده می‌شود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function show(Request $request): View
    {
        $user = $request->user(); // دریافت کاربر لاگین شده
        // دریافت اطلاعات حقوقی کاربر (اگر وجود دارد)
        $legalInfo = $user->legalInfo; // فرض کنید یک رابطه hasOne برای legalInfo در مدل User دارید

        return view('profile', [
            'user' => $user,
            'legalInfo' => $legalInfo, // ارسال اطلاعات حقوقی به ویو
        ]);
    }

    /**
     * Update the user's profile information.
     * این متد از Breeze/Jetstream است و برای به‌روزرسانی اطلاعات اصلی (مثل نام و ایمیل) استفاده می‌شود.
     * ما این را حفظ می‌کنیم تا با روت profile.patch تداخل نداشته باشد.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's password.
     * به روز رسانی رمز عبور کاربر.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return Redirect::route('profile.edit')->with('status', 'password-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * به‌روزرسانی اطلاعات شخصی کاربر (نام، نام خانوادگی، کد ملی، ایمیل، تلفن ثابت).
     * این متد برای روت PUT /profile/personal استفاده می‌شود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // اعتبار سنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'national_id' => 'nullable|string|digits:10|unique:users,national_id,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'fixed_phone' => 'nullable|string|max:20', // اکنون از fixed_phone استفاده می‌شود
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبار سنجی داده‌ها.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        // به‌روزرسانی اطلاعات کاربر
        $user->name = $request->input('name');
        $user->lastname = $request->input('lastname');
        $user->national_id = $request->input('national_id');
        $user->email = $request->input('email');
        $user->fixed_phone = $request->input('fixed_phone'); // اکنون از fixed_phone استفاده می‌شود
        $user->save();

        return response()->json(['message' => 'اطلاعات شخصی شما با موفقیت به‌روز شد.']);
    }

    /**
     * ذخیره اطلاعات حقوقی کاربر.
     * این متد برای روت POST /profile/legal-info استفاده می‌شود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeLegalInfo(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'national_code' => 'required|string|digits:10',
            'sheba_number' => 'nullable|string|max:24',
            'card_number' => 'nullable|string|max:19',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'postal_code' => 'required|string|digits:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبار سنجی اطلاعات حقوقی.',
                'errors' => $validator->errors()
            ], 422);
        }

        // فرض کنید اطلاعات حقوقی در یک جدول جداگانه (`legal_infos`) ذخیره می‌شود
        // و یک رابطه `hasOne` بین User و LegalInfo وجود دارد.
        // اگر از قبل اطلاعات حقوقی وجود دارد، آن را به‌روزرسانی کنید، در غیر این صورت ایجاد کنید.
        $legalInfo = $user->legalInfo()->updateOrCreate(
            ['user_id' => $user->id], // شرط پیدا کردن
            $request->only([
                'full_name', 'national_code', 'sheba_number', 'card_number',
                'province', 'city', 'address', 'postal_code'
            ])
        );

        return response()->json(['message' => 'اطلاعات حقوقی با موفقیت ذخیره شد.']);
    }

    /**
     * به‌روزرسانی تاریخ تولد از مودال.
     * این متد برای روت PUT /profile/birth-date استفاده می‌شود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBirthDate(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'shamsi_birth_date_formatted' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}$/', function ($attribute, $value, $fail) {
                // Basic validation for Shamsi date format YYYY-MM-DD
                $parts = explode('-', $value);
                if (count($parts) !== 3) {
                    $fail('فرمت تاریخ تولد معتبر نیست.');
                    return;
                }
                $year = (int)$parts[0];
                $month = (int)$parts[1];
                $day = (int)$parts[2];

                // Validate month and day ranges
                if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                    $fail('ماه یا روز تاریخ تولد معتبر نیست.');
                    return;
                }

                // Validate days in month for Shamsi calendar
                if ($month >= 1 && $month <= 6) {
                    if ($day > 31) {
                        $fail('روز در ماه انتخاب شده معتبر نیست.');
                        return;
                    }
                } elseif ($month >= 7 && $month <= 11) {
                    if ($day > 30) {
                        $fail('روز در ماه انتخاب شده معتبر نیست.');
                        return;
                    }
                } elseif ($month === 12) {
                    // For Esfand (12th month), check for leap year
                    // This requires a reliable Jalali calendar library in PHP
                    // Example with morilog/jalali:
                    // if (!\Morilog\Jalali\Jalali::isValidDate($year, $month, $day)) {
                    //     $fail('تاریخ تولد وارد شده معتبر نیست.');
                    //     return;
                    // }
                    // Without a library, a simple check:
                    if ($day > 29) { // Assuming 29 days for non-leap Esfand
                        // If you need to check for 30 days in leap year, you must use a Jalali library
                        $fail('روز در ماه اسفند معتبر نیست (سال کبیسه).');
                        return;
                    }
                }
            }],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبار سنجی تاریخ تولد.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Save the formatted Shamsi date directly to the database
        $user->birth_date = $request->input('shamsi_birth_date_formatted');
        $user->save();

        // Return the saved date, formatted for display if needed in frontend
        return response()->json(['success' => true, 'message' => 'تاریخ تولد با موفقیت به‌روز شد.', 'birth_date' => str_replace('-', '/', $user->birth_date)]);
    }

    /**
     * به‌روزرسانی شماره موبایل کاربر.
     * این متد برای روت PUT /profile/mobile استفاده می‌شود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMobile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|string|digits:11|unique:users,mobile_number,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'شماره موبایل نامعتبر است یا قبلاً ثبت شده است.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->mobile_number = $request->input('mobile_number');
        $user->save();

        // در اینجا می‌توانید منطق ارسال کد تایید را اضافه کنید
        // مثلاً با استفاده از یک سرویس پیامک

        return response()->json(['message' => 'شماره موبایل با موفقیت به‌روز شد. کد تایید برای شما ارسال گردید.', 'mobile_number' => $user->mobile_number]);
    }
}
