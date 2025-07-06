<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // برای استفاده از Rule::unique

class ProductStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * تعیین می‌کند که آیا کاربر مجاز به انجام این درخواست است یا خیر.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // در اکثر موارد، این باید true باشد مگر اینکه منطق مجوز خاصی داشته باشید.
        // مثلاً فقط کاربران ادمین بتوانند محصول اضافه/آپدیت کنند.
        // برای سادگی، فعلاً true قرار می‌دهیم.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * دریافت قوانین اعتبارسنجی که برای درخواست اعمال می‌شوند.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // تشخیص اینکه آیا درخواست برای ایجاد (store) است یا به‌روزرسانی (update)
        $isUpdate = $this->route('product') !== null;
        $productId = $isUpdate ? $this->route('product')->id : null;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                // عنوان باید در جدول محصولات یکتا باشد، به جز برای محصول فعلی در حالت به‌روزرسانی
                Rule::unique('products')->ignore($productId),
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0', // قیمت باید عددی و حداقل 0 باشد
            'stock' => 'required|integer|min:0', // موجودی باید عدد صحیح و حداقل 0 باشد
            'category_id' => 'required|integer|exists:categories,id', // باید یک category_id معتبر باشد
            'image' => [
                $isUpdate ? 'nullable' : 'required', // تصویر در هنگام ایجاد الزامی، در به‌روزرسانی اختیاری
                'image', // باید یک فایل تصویری باشد
                'mimes:jpeg,png,jpg,gif,webp', // فرمت‌های مجاز
                'max:5120', // حداکثر حجم 5 مگابایت (5120 کیلوبایت)
            ],
            'remove_image' => 'boolean', // برای به‌روزرسانی: یک فیلد بولی برای حذف تصویر موجود
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * دریافت پیام‌های خطای سفارشی برای قوانین اعتبارسنجی تعریف شده.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان محصول الزامی است.',
            'title.string' => 'عنوان محصول باید متنی باشد.',
            'title.max' => 'عنوان محصول نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'title.unique' => 'این عنوان محصول قبلاً ثبت شده است.',
            'description.string' => 'توضیحات محصول باید متنی باشد.',
            'price.required' => 'قیمت محصول الزامی است.',
            'price.numeric' => 'قیمت محصول باید عددی باشد.',
            'price.min' => 'قیمت محصول نمی‌تواند منفی باشد.',
            'stock.required' => 'موجودی محصول الزامی است.',
            'stock.integer' => 'موجودی محصول باید یک عدد صحیح باشد.',
            'stock.min' => 'موجودی محصول نمی‌تواند منفی باشد.',
            'category_id.required' => 'انتخاب دسته بندی محصول الزامی است.',
            'category_id.integer' => 'شناسه دسته بندی باید یک عدد صحیح باشد.',
            'category_id.exists' => 'دسته بندی انتخاب شده معتبر نیست.',
            'image.required' => 'تصویر محصول الزامی است.',
            'image.image' => 'فایل آپلود شده باید یک تصویر باشد.',
            'image.mimes' => 'فرمت تصویر باید jpeg، png، jpg، gif یا webp باشد.',
            'image.max' => 'حجم تصویر نباید بیشتر از ۵ مگابایت باشد.',
            'remove_image.boolean' => 'فیلد حذف تصویر باید بولی باشد.',
        ];
    }
}
