<?php
// File: app/Http/Requests/Cart/UpdateMultipleCartItemsRequest.php
namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMultipleCartItemsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * تعیین می‌کند که آیا کاربر مجاز به انجام این درخواست است یا خیر.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // در اکثر موارد، اگر کاربر احراز هویت شده باشد یا مهمان باشد، مجاز است.
        // منطق پیچیده‌تر مجوز می‌تواند در اینجا اضافه شود.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * قوانین اعتبارسنجی که برای درخواست اعمال می‌شوند را دریافت می‌کند.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'updates' => ['required', 'array'], // باید یک آرایه باشد.
            'updates.*.product_id' => ['required', 'integer', 'exists:products,id'], // هر آیتم باید product_id معتبر داشته باشد.
            'updates.*.quantity' => ['required', 'integer', 'min:0'], // هر آیتم باید quantity معتبر داشته باشد (0 برای حذف).
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * پیام‌های خطا برای قوانین اعتبارسنجی تعریف شده را دریافت می‌کند.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'updates.required' => 'آرایه به‌روزرسانی‌ها الزامی است.',
            'updates.array' => 'فرمت به‌روزرسانی‌ها باید آرایه باشد.',
            'updates.*.product_id.required' => 'شناسه محصول برای هر آیتم به‌روزرسانی الزامی است.',
            'updates.*.product_id.integer' => 'شناسه محصول برای هر آیتم به‌روزرسانی باید یک عدد صحیح باشد.',
            'updates.*.product_id.exists' => 'محصول انتخاب شده برای یکی از آیتم‌ها معتبر نیست.',
            'updates.*.quantity.required' => 'تعداد محصول برای هر آیتم به‌روزرسانی الزامی است.',
            'updates.*.quantity.integer' => 'تعداد محصول برای هر آیتم به‌روزرسانی باید یک عدد صحیح باشد.',
            'updates.*.quantity.min' => 'تعداد محصول برای هر آیتم به‌روزرسانی نمی‌تواند منفی باشد.',
        ];
    }
}
