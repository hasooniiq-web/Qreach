<?php

namespace App\Http\Requests\Api\Auth;

use App\Services\IraqiPhone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Proves phone ownership via OTP, then sets (or replaces) the account's
 * password in the same call. Serves two cases with the same flow: an
 * existing phone-only account setting its first-ever password, and a
 * customer who forgot their password.
 */
class SetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => IraqiPhone::normalize($this->input('phone')) ?? $this->input('phone')]);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'code' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'in:ios,android,web'],
            'push_token' => ['nullable', 'string'],
            'push_provider' => ['nullable', 'in:fcm,apns'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
        ];
    }
}
