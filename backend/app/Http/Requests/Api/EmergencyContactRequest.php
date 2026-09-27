<?php

namespace App\Http\Requests\Api;

use App\Rules\PlainTextOnly;
use App\Services\IraqiPhone;
use Illuminate\Foundation\Http\FormRequest;

class EmergencyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Accepts the leading trunk zero ("07...") the same as "7..." or a
     *  number already written with a country code, before it is stored. */
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
            'relationship' => ['nullable', 'string', 'max:40', new PlainTextOnly],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'visible' => ['sometimes', 'boolean'],
        ];
    }
}
