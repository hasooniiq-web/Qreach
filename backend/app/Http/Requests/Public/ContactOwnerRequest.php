<?php

namespace App\Http\Requests\Public;

use App\Rules\PlainTextOnly;
use Illuminate\Foundation\Http\FormRequest;

class ContactOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // A reason chip alone is enough — the free-text message is optional when one is chosen.
            'reason' => ['nullable', 'in:damage,open,question,other'],
            'message' => ['required_without:reason', 'nullable', 'string', 'max:500', new PlainTextOnly],
            'visitor_name' => ['nullable', 'string', 'max:100', new PlainTextOnly],
            'visitor_phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[1-9]\d{7,14}$/'],
        ];
    }
}
