<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'code' => ['required', 'string', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'in:ios,android,web'],
            'push_token' => ['nullable', 'string'],
            'push_provider' => ['nullable', 'in:fcm,apns'],
        ];
    }
}
