<?php

namespace App\Http\Requests\Api\Auth;

use App\Rules\PlainTextOnly;
use App\Services\IraqiPhone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Step 1 of creating a new account: name, phone and password. The phone
 * isn't verified yet — that happens in RegisterVerifyController@verify, via
 * the same OTP machinery as everything else — so nothing here is persisted
 * as a real, usable account until that step succeeds.
 */
class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:100', new PlainTextOnly],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
        ];
    }
}
