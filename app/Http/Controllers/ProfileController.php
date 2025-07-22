<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use App\Models\LegalInfo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $legalInfo = $user->legalInfo;

        return view('profile', [
            'user' => $user,
            'legalInfo' => $legalInfo,
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * @param ProfileUpdateRequest $request
     * @return RedirectResponse
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Update profile_completed based on name and lastname presence
        if (!empty($user->name) && !empty($user->lastname) && !$user->profile_completed) { // Changed 'last_name' to 'lastname'
            $user->profile_completed = true;
            Log::info('ProfileController: profile_completed set to true for user ' . $user->id . ' via update method.');
        } elseif ((empty($user->name) || empty($user->lastname)) && $user->profile_completed) { // Changed 'last_name' to 'lastname'
            // Optional: Revert profile_completed if name/lastname become empty
            // $user->profile_completed = false;
            // Log::info('ProfileController: profile_completed set to false for user ' . $user->id . ' due to missing name/lastname.');
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's password.
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
     *
     * @param Request $request
     * @return RedirectResponse
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255', // Changed 'last_name' to 'lastname'
            'national_id' => 'nullable|string|digits:10|unique:users,national_id,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'fixed_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبار سنجی داده‌ها.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->name = $request->input('name');
        $user->lastname = $request->input('lastname'); // Changed 'last_name' to 'lastname'
        $user->national_id = $request->input('national_id');
        $user->email = $request->input('email');
        $user->fixed_phone = $request->input('fixed_phone');
        
        // Update profile_completed if name and lastname are now present
        if (!empty($user->name) && !empty($user->lastname) && !$user->profile_completed) { // Changed 'last_name' to 'lastname'
            $user->profile_completed = true;
            Log::info('ProfileController: profile_completed set to true for user ' . $user->id . ' via updateProfile method.');
        } elseif ((empty($user->name) || empty($user->lastname)) && $user->profile_completed) { // Changed 'last_name' to 'lastname'
            // Optional: Revert profile_completed if name/lastname become empty
            // $user->profile_completed = false;
            // Log::info('ProfileController: profile_completed set to false for user ' . $user->id . ' due to missing name/lastname in updateProfile.');
        }

        $user->save();

        return response()->json(['message' => 'اطلاعات شخصی شما با موفقیت به‌روز شد.']);
    }

    /**
     * ذخیره اطلاعات حقوقی کاربر.
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

        $legalInfo = $user->legalInfo()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'full_name', 'national_code', 'sheba_number', 'card_number',
                'province', 'city', 'address', 'postal_code'
            ])
        );

        return response()->json(['message' => 'اطلاعات حقوقی با موفقیت ذخیره شد.']);
    }

    /**
     * به‌روزرسانی تاریخ تولد از مودال.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBirthDate(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'shamsi_birth_date_formatted' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}$/', function ($attribute, $value, $fail) {
                $parts = explode('-', $value);
                if (count($parts) !== 3) {
                    $fail('فرمت تاریخ تولد معتبر نیست.');
                    return;
                }
                $year = (int)$parts[0];
                $month = (int)$parts[1];
                $day = (int)$parts[2];

                if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                    $fail('ماه یا روز تاریخ تولد معتبر نیست.');
                    return;
                }

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
                    if ($day > 29) {
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

        $user->birth_date = $request->input('shamsi_birth_date_formatted');
        $user->save();

        return response()->json(['success' => true, 'message' => 'تاریخ تولد با موفقیت به‌روز شد.', 'birth_date' => str_replace('-', '/', $user->birth_date)]);
    }

    /**
     * به‌روزرسانی شماره موبایل کاربر.
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

        return response()->json(['message' => 'شماره موبایل با موفقیت به‌روز شد. کد تایید برای شما ارسال گردید.', 'mobile_number' => $user->mobile_number]);
    }
}
