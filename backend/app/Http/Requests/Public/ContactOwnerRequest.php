<?php

namespace App\Http\Requests\Public;

use App\Rules\PlainTextOnly;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A quick report: the visitor taps one predefined reason. There is deliberately
 * no free-text "other" reason; conversation happens afterwards in the thread.
 */
class ContactOwnerRequest extends FormRequest
{
    public const REASONS = ['damage', 'malfunction', 'safety', 'assistance'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:'.implode(',', self::REASONS)],
            'visitor_name' => ['nullable', 'string', 'max:100', new PlainTextOnly],
            'visitor_phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[1-9]\d{7,14}$/'],
        ];
    }

    public function messages(): array
    {
        return ['reason.required' => 'اختر سبب البلاغ.', 'reason.in' => 'سبب البلاغ غير صالح.'];
    }
}
