<?php

namespace App\Http\Requests\Public;

use App\Rules\PlainTextOnly;
use Illuminate\Foundation\Http\FormRequest;

class ImportantMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:300', new PlainTextOnly],
        ];
    }
}
