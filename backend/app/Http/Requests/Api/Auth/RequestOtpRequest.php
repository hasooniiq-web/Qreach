<?php

namespace App\Http\Requests\Api\Auth;

use App\Services\IraqiPhone;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Accepts "07..." or "7..." the same as "+964...": the app already sends
     *  the international form, but nothing that reaches this endpoint should
     *  be rejected just for keeping the leading trunk zero. */
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
        ];
    }
}
