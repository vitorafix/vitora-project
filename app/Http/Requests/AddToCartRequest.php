<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // در اکثر موارد، اگر کنترلر به middleware 'auth' یا 'guest' مجهز باشد،
        // اینجا می‌توانید true برگردانید. منطق دقیق‌تر احراز هویت در سرویس یا Middleware انجام می‌شود.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'شناسه محصول الزامی است.',
            'product_id.integer' => 'شناسه محصول باید عددی باشد.',
            'product_id.exists' => 'محصول یافت نشد.',
            'quantity.required' => 'تعداد محصول الزامی است.',
            'quantity.integer' => 'تعداد محصول باید عددی باشد.',
            'quantity.min' => 'تعداد محصول حداقل باید ۱ باشد.',
        ];
    }
}
