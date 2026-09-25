<?php

namespace App\Http\Requests\Api;

use App\Rules\PlainTextOnly;
use Illuminate\Foundation\Http\FormRequest;

class EmergencyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
