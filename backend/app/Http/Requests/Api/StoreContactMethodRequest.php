<?php

namespace App\Http\Requests\Api;

use App\Rules\PlainTextOnly;
use App\Services\ContactPlatforms;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // "phone" has its own endpoint (a Basic feature); everything else is a Plus method.
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(array_diff_key(ContactPlatforms::all(), ['phone' => 1])))],
            'value' => ['required', 'string', 'max:200'],
            'label' => ['nullable', 'required_if:type,other', 'string', 'max:40', new PlainTextOnly],
            'visible' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $v->errors()->has('value') && ! $v->errors()->has('type') && $this->cleanValue() === null) {
                $v->errors()->add('value', 'القيمة غير صالحة لهذه المنصة.');
            }
        });
    }

    /** The value cleaned for its platform (e.g. "@name" -> "name"), or null if invalid. */
    public function cleanValue(?string $type = null): ?string
    {
        return ContactPlatforms::normalize($type ?? (string) $this->input('type'), (string) $this->input('value'));
    }
}
