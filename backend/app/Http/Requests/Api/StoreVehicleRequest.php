<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plate_type' => ['nullable', 'in:private,government,truck,taxi,agricultural'],
            // One letter, as on an Iraqi plate.
            'plate_letter' => ['nullable', 'string', 'regex:/^[\p{L}]$/u'],
            'plate_governorate' => ['nullable', 'string', 'max:40'],
            'plate_style' => ['nullable', 'in:english,german,other'],
            'vin' => ['nullable', 'string', 'max:32', 'regex:/^[A-HJ-NPR-Z0-9]+$/i', 'unique:vehicles,vin'],
            'nickname' => ['nullable', 'string', 'max:100'],
            // Up to six digits (an Iraqi plate); other countries keep their own format.
            'plate_number' => ['required', 'string', 'max:32', function ($attribute, $value, $fail) {
                $country = strtoupper((string) ($this->input('plate_country') ?: 'IQ'));
                if ($country === 'IQ' && preg_match('/^[0-9٠-٩]{1,6}$/u', (string) $value) !== 1) {
                    $fail('رقم اللوحة أرقام فقط وبحد أقصى 6 مراتب.');
                }
            }],
            'plate_country' => ['nullable', 'string', 'size:2'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'in:sedan,suv,pickup,truck,bus,motorcycle,van,other'],
            'description' => ['nullable', 'string', 'max:1000'],
            'privacy_settings' => ['nullable', 'array'],
            'privacy_settings.*' => ['boolean'],
        ];
    }
}
