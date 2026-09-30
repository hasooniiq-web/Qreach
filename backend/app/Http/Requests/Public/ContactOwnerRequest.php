<?php

namespace App\Http\Requests\Public;

use App\Rules\PlainTextOnly;
use App\Services\IraqiPhone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A quick report: the visitor taps one predefined reason, or "other" with a
 * short free-text description of their own. Further back-and-forth still
 * happens afterwards in the thread.
 */
class ContactOwnerRequest extends FormRequest
{
    public const REASONS = ['blocking', 'damage', 'malfunction', 'safety', 'assistance', 'other'];

    public function authorize(): bool
    {
        return true;
    }

    /** The visitor's own number, typed with no fixed prefix — accept it with
     *  or without the leading trunk zero, same as every other phone field. */
    protected function prepareForValidation(): void
    {
        if ($this->filled('visitor_phone')) {
            $this->merge(['visitor_phone' => IraqiPhone::normalize($this->input('visitor_phone')) ?? $this->input('visitor_phone')]);
        }
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:'.implode(',', self::REASONS)],
            // Only "other" needs a description; every other reason already
            // has a fixed label (composeContactMessage), so this stays empty
            // and unused for them even if the client sent something.
            'other_details' => ['required_if:reason,other', 'nullable', 'string', 'max:500', new PlainTextOnly],
            'visitor_name' => ['nullable', 'string', 'max:100', new PlainTextOnly],
            'visitor_phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[1-9]\d{7,14}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'اختر سبب البلاغ.',
            'reason.in' => 'سبب البلاغ غير صالح.',
            'other_details.required_if' => 'اكتب تفاصيل السبب.',
        ];
    }
}
