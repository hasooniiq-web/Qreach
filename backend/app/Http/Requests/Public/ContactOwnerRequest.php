<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A quick report: the visitor taps one of six predefined reasons. Nothing is typed — not a
 * description, not a name, not a number. Talking back to the owner happens in the app.
 */
class ContactOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:'.implode(',', \App\Support\ContactReasons::keys())],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'اختر سبب البلاغ.',
            'reason.in' => 'سبب البلاغ غير صالح.',
        ];
    }
}
