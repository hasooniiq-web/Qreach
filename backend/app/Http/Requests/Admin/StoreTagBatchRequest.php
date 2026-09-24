<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'tag_type' => ['required', 'in:qr,nfc'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
