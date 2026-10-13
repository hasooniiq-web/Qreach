<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plate_type' => ['nullable', 'in:private,government,truck,taxi,agricultural'],
            // One letter; a longer value saved earlier may be sent back unchanged.
            'plate_letter' => ['nullable', 'string', function ($attribute, $value, $fail) {
                $current = $this->route('vehicle')?->plate_letter;
                if ($value !== null && $value !== $current && preg_match('/^[\p{L}]$/u', (string) $value) !== 1) {
                    $fail('الحرف يجب أن يكون حرفًا واحدًا.');
                }
            }],
            'plate_governorate' => ['nullable', 'string', 'max:40'],
            'vin' => ['nullable', 'string', 'max:32', 'regex:/^[A-HJ-NPR-Z0-9]+$/i', 'unique:vehicles,vin,'.$this->route('vehicle')?->id],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:100'],
            'plate_number' => ['sometimes', 'string', 'max:32', function ($attribute, $value, $fail) {
                $vehicle = $this->route('vehicle');
                $country = strtoupper((string) ($this->input('plate_country') ?: ($vehicle?->plate_country ?: 'IQ')));
                if ($country === 'IQ' && $value !== $vehicle?->plate_number && preg_match('/^[0-9٠-٩]{1,6}$/u', (string) $value) !== 1) {
                    $fail('رقم اللوحة أرقام فقط وبحد أقصى 6 مراتب.');
                }
            }],
            'plate_country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'make' => ['sometimes', 'nullable', 'string', 'max:100'],
            'model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'vehicle_type' => ['sometimes', 'in:sedan,suv,pickup,truck,bus,motorcycle,van,other'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:active,inactive,archived'],
            'privacy_settings' => ['sometimes', 'array'],
            'privacy_settings.*' => ['boolean'],
        ];
    }
}
