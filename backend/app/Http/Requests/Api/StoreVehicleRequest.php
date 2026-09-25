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
            'plate_letter' => ['nullable', 'string', 'max:4', 'regex:/^[\p{L}]{1,4}$/u'],
            'plate_governorate' => ['nullable', 'string', 'max:40'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'max:32'],
            'plate_country' => ['nullable', 'string', 'size:2'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'in:sedan,suv,truck,motorcycle,van,other'],
            'description' => ['nullable', 'string', 'max:1000'],
            'privacy_settings' => ['nullable', 'array'],
            'privacy_settings.*' => ['boolean'],
        ];
    }
}
