<?php

namespace App\Http\Requests\Public;

use App\Rules\PlainTextOnly;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Section 13: emergency action requires explicit confirmation — never
 * triggered by a single accidental tap.
 */
class EmergencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirmed' => ['required', 'accepted'],
            'message' => ['nullable', 'string', 'max:300', new PlainTextOnly()],
        ];
    }
}
