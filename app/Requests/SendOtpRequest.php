<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Allow all users (authenticated or guest) to request OTP.
        // Further logic for user existence is handled in the controller.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'size:11'],
            'website' => ['sometimes', 'nullable', 'string'], // For honeypot
            'redirect_to_profile' => ['sometimes', 'nullable', 'string'], // For profile update flow
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
            'mobile_number.regex' => 'فرمت شماره موبایل اشتباه است.',
            'mobile_number.required' => 'شماره موبایل الزامی است.',
            'mobile_number.string' => 'شماره موبایل باید یک رشته باشد.',
            'mobile_number.size' => 'شماره موبایل باید ۱۱ رقم باشد.',
        ];
    }
}
