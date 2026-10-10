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
        $this->merge([
            'quantity' => \App\Support\NumberInput::clean($this->input('quantity')),
            // The dashboard now creates unified codes only; the old kinds stay accepted for scripts/tests.
            'tag_type' => $this->input('tag_type', 'code'),
        ]);
    }

    public function messages(): array
    {
        $max = number_format((int) SystemSetting::get('qr_batch_max_quantity', 1000));

        return [
            'quantity.required' => 'أدخل الكمية.',
            'quantity.integer' => 'الكمية يجب أن تكون رقمًا صحيحًا.',
            'quantity.min' => 'الكمية يجب ألا تقل عن 1.',
            'quantity.max' => "الكمية يجب ألا تتجاوز {$max} رمز في الدفعة الواحدة.",
        ];
    }

    public function attributes(): array
    {
        return ['quantity' => 'الكمية', 'tag_type' => 'نوع الرمز', 'notes' => 'الملاحظات'];
    }

    public function rules(): array
    {
        // Admin-configurable via the settings page — see SystemSettingController.
        $max = (int) SystemSetting::get('qr_batch_max_quantity', 1000);

        return [
            'quantity' => ['required', 'integer', 'min:1', "max:{$max}"],
            'tag_type' => ['required', 'in:code,qr,nfc'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
