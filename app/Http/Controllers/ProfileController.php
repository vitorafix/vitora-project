<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User; // مطمئن شوید مدل User ایمپورت شده است
use Illuminate\Support\Facades\Hash; // اضافه کردن Hash برای هش کردن رمز عبور
use Illuminate\Validation\Rules\Password; // اضافه کردن Password Rule

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
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
        $request->validateWithBag('userDeletion', [
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
     * Display the form for completing user profile information.
     * نمایش فرم برای تکمیل اطلاعات پروفایل کاربر.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function completeProfileForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // اگر پروفایل قبلاً تکمیل شده باشد، به صفحه اصلی هدایت کنید
        if ($user->profile_completed) {
            return Redirect::route('home');
        }

        return view('profile.complete', [
            'user' => $user,
        ]);
    }


    /**
     * Update the user's profile information, specifically for initial completion.
     * اطلاعات پروفایل کاربر را، به ویژه برای تکمیل اولیه، به‌روزرسانی می‌کند.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function completeProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        // 1. اعتبارسنجی ورودی‌ها
        $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            // mobileNumber نیاز نیست اینجا آپدیت شود چون برای احراز هویت اولیه استفاده شده
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postalCode' => ['required', 'string', 'regex:/^[0-9]{10}$/'], // کد پستی 10 رقمی
        ]);

        // 2. به‌روزرسانی اطلاعات کاربر
        $user->name = $request->fullName;
        // $user->mobile_number = $request->mobileNumber; // این خط را حذف یا کامنت می‌کنیم چون شماره موبایل هنگام ثبت نام اولیه تعیین شده
        $user->address = $request->address;
        $user->city = $request->city;
        $user->province = $request->province;
        $user->postal_code = $request->postalCode;
        $user->profile_completed = true; // یک فیلد برای نشان دادن تکمیل پروفایل

        $user->save();

        // 3. هدایت کاربر به صفحه اصلی با پیام موفقیت
        return Redirect::route('home')->with('status', 'profile-completed');
    }
}
