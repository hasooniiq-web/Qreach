<?php

namespace App\Http\Requests\Admin;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreTagBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['quantity' => \App\Support\NumberInput::clean($this->input('quantity'))]);
    }

    public function rules(): array
    {
        // Admin-configurable via the settings page — see SystemSettingController.
        $max = (int) SystemSetting::get('qr_batch_max_quantity', 1000);

        return [
            'quantity' => ['required', 'integer', 'min:1', "max:{$max}"],
            'tag_type' => ['required', 'in:qr,nfc'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
