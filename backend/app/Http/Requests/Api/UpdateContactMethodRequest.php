<?php

namespace App\Http\Requests\Api;

use App\Services\ContactPlatforms;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['sometimes', 'nullable', 'string', 'max:200'],
            'visible' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $method = $this->user()->contactMethods()->find($this->route('method'));

            if ($method && $this->filled('value') && $this->cleanValue($method->type) === null) {
                $v->errors()->add('value', 'القيمة غير صالحة لهذه المنصة.');
            }
        });
    }

    public function cleanValue(string $type): ?string
    {
        return ContactPlatforms::normalize($type, (string) $this->input('value'));
    }
}
