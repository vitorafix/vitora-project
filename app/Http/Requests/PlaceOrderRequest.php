<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * تعیین می‌کند که آیا کاربر مجاز به ارسال این درخواست است یا خیر.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // اجازه می‌دهیم هم کاربران لاگین شده و هم مهمان‌ها سفارش ثبت کنند.
        // منطق پیچیده‌تر احراز هویت می‌تواند در اینجا اضافه شود.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * دریافت قوانین اعتبارسنجی که به درخواست اعمال می‌شوند.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/|max:11', // فرمت شماره موبایل ایران
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'postal_code' => 'required|string|regex:/^[0-9]{10}$/', // کد پستی 10 رقمی
            'shipping_method' => 'required|in:post,courier', // روش‌های ارسال مجاز
            'payment_method' => 'required|in:online,cod', // روش‌های پرداخت مجاز
            'terms_agree' => 'accepted', // چک‌باکس قوانین باید تیک خورده باشد
            'delivery_notes' => 'nullable|string|max:500', // یادداشت برای پیک اختیاری است
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * دریافت پیام‌های خطای سفارشی برای قوانین اعتبارسنجی.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'وارد کردن نام الزامی است.',
            'last_name.required' => 'وارد کردن نام خانوادگی الزامی است.',
            'phone_number.required' => 'وارد کردن شماره تلفن الزامی است.',
            'phone_number.regex' => 'فرمت شماره تلفن صحیح نیست. مثال: 09123456789',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از ۱۱ کاراکتر باشد.',
            'address.required' => 'وارد کردن آدرس کامل الزامی است.',
            'city.required' => 'وارد کردن شهر الزامی است.',
            'province.required' => 'وارد کردن استان الزامی است.',
            'postal_code.required' => 'وارد کردن کد پستی الزامی است.',
            'postal_code.regex' => 'کد پستی باید ۱۰ رقمی باشد.',
            'shipping_method.required' => 'انتخاب روش ارسال الزامی است.',
            'shipping_method.in' => 'روش ارسال انتخاب شده معتبر نیست.',
            'payment_method.required' => 'انتخاب روش پرداخت الزامی است.',
            'payment_method.in' => 'روش پرداخت انتخاب شده معتبر نیست.',
            'terms_agree.accepted' => 'برای ثبت سفارش، باید قوانین و مقررات را بپذیرید.',
        ];
    }
}
