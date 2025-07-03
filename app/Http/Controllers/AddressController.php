<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule; // Added for unique validation rule

class AddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     * نمایش لیستی از آدرس‌های کاربر.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $user = Auth::user();
        $addresses = $user->addresses()->orderBy('is_default', 'desc')->get(); // آدرس‌های پیش‌فرض را اول نمایش می‌دهد

        return view('profile.addresses', compact('addresses'));
    }

    /**
     * Show the form for creating a new address.
     * نمایش فرم برای ایجاد یک آدرس جدید.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('profile.address-create-edit');
    }

    /**
     * Store a newly created address in storage.
     * ذخیره یک آدرس جدید در پایگاه داده.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'postal_code' => [
                'required', // Made required as per previous request
                'string',
                'regex:/^[0-9]{10}$/',
                Rule::unique('addresses', 'postal_code')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }), // Check uniqueness only for the current user's addresses
            ],
            'phone_number' => [
                'nullable',
                'string',
                'regex:/^09[0-9]{9}$/',
                'max:11',
                Rule::unique('addresses', 'phone_number')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }), // Check uniqueness only for the current user's addresses
            ],
            'is_default' => ['boolean'],
        ], [
            'postal_code.unique' => 'این کد پستی قبلاً برای یکی از آدرس‌های شما ثبت شده است.',
            'postal_code.required' => 'وارد کردن کد پستی الزامی است.',
            'postal_code.regex' => 'کد پستی باید ۱۰ رقم و فقط شامل اعداد باشد.',
            'phone_number.unique' => 'این شماره تلفن قبلاً برای یکی از آدرس‌های شما ثبت شده است.',
            'phone_number.regex' => 'فرمت شماره تلفن صحیح نیست. (مثال: 09123456789)',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از ۱۱ رقم باشد.',
        ]);

        $user = Auth::user();

        // اگر آدرس جدید به عنوان پیش‌فرض تنظیم شود، بقیه آدرس‌های کاربر را از حالت پیش‌فرض خارج کنید
        if (isset($validated['is_default']) && $validated['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }
        
        // اگر این اولین آدرس کاربر است، آن را به عنوان پیش فرض تنظیم کنید
        if ($user->addresses->isEmpty()) {
            $validated['is_default'] = true;
        }

        $user->addresses()->create($validated);

        return redirect()->route('profile.addresses.index')->with('status', 'آدرس با موفقیت اضافه شد.');
    }

    /**
     * Show the form for editing the specified address.
     * نمایش فرم برای ویرایش آدرس مشخص شده.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Address $address): View|RedirectResponse
    {
        // اطمینان از اینکه کاربر فقط می‌تواند آدرس‌های خودش را ویرایش کند
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        return view('profile.address-create-edit', compact('address'));
    }

    /**
     * Update the specified address in storage.
     * به‌روزرسانی آدرس مشخص شده در پایگاه داده.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Address $address): RedirectResponse
    {
        // اطمینان از اینکه کاربر فقط می‌تواند آدرس‌های خودش را ویرایش کند
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'postal_code' => [
                'required', // Made required as per previous request
                'string',
                'regex:/^[0-9]{10}$/',
                Rule::unique('addresses', 'postal_code')->ignore($address->id)->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }), // Check uniqueness for the current user's addresses, ignoring the current address
            ],
            'phone_number' => [
                'nullable',
                'string',
                'regex:/^09[0-9]{9}$/',
                'max:11',
                Rule::unique('addresses', 'phone_number')->ignore($address->id)->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }), // Check uniqueness for the current user's addresses, ignoring the current address
            ],
            'is_default' => ['boolean'],
        ], [
            'postal_code.unique' => 'این کد پستی قبلاً برای یکی از آدرس‌های شما ثبت شده است.',
            'postal_code.required' => 'وارد کردن کد پستی الزامی است.',
            'postal_code.regex' => 'کد پستی باید ۱۰ رقم و فقط شامل اعداد باشد.',
            'phone_number.unique' => 'این شماره تلفن قبلاً برای یکی از آدرس‌های شما ثبت شده است.',
            'phone_number.regex' => 'فرمت شماره تلفن صحیح نیست. (مثال: 09123456789)',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از ۱۱ رقم باشد.',
        ]);

        $user = Auth::user();

        // اگر آدرس به عنوان پیش‌فرض تنظیم شود، بقیه آدرس‌های کاربر را از حالت پیش‌فرض خارج کنید
        if (isset($validated['is_default']) && $validated['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }
        
        // اگر این تنها آدرس کاربر است، باید پیش‌فرض بماند
        if ($user->addresses->count() === 1 && !$validated['is_default']) {
            // Only force default if it's the only address AND it's not already set as default
            $validated['is_default'] = true;
        }


        $address->update($validated);

        return redirect()->route('profile.addresses.index')->with('status', 'آدرس با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified address from storage.
     * حذف آدرس مشخص شده از پایگاه داده.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Address $address): RedirectResponse
    {
        // اطمینان از اینکه کاربر فقط می‌تواند آدرس‌های خودش را حذف کند
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        // بررسی کنید که آیا این آخرین آدرس کاربر است
        if (Auth::user()->addresses()->count() === 1) {
            return redirect()->route('profile.addresses.index')->with('error', 'شما نمی‌توانید آخرین آدرس خود را حذف کنید. حداقل یک آدرس باید وجود داشته باشد.');
        }
        
        // اگر آدرس پیش‌فرض است و آدرس‌های دیگری وجود دارد، باید آدرس پیش‌فرض جدیدی تعیین شود (اختیاری)
        if ($address->is_default && Auth::user()->addresses()->count() > 1) {
            // یک آدرس دیگر را به عنوان پیش‌فرض تنظیم کنید.
            Auth::user()->addresses()->where('id', '!=', $address->id)->first()->update(['is_default' => true]);
        }

        $address->delete();

        return redirect()->route('profile.addresses.index')->with('status', 'آدرس با موفقیت حذف شد.');
    }

    /**
     * Set the specified address as the default address for the user.
     * تنظیم آدرس مشخص شده به عنوان آدرس پیش‌فرض برای کاربر.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDefault(Request $request, Address $address): RedirectResponse
    {
        // اطمینان حاصل کنید که آدرس متعلق به کاربر فعلی است
        if ($request->user()->id !== $address->user_id) {
            abort(403);
        }

        // همه آدرس‌های کاربر را از حالت پیش‌فرض خارج کنید
        $request->user()->addresses()->update(['is_default' => false]);

        // آدرس انتخاب شده را به عنوان پیش‌فرض تنظیم کنید
        $address->update(['is_default' => true]);

        return redirect()->route('profile.addresses.index')->with('status', 'آدرس پیش‌فرض با موفقیت تغییر یافت.');
    }
}
