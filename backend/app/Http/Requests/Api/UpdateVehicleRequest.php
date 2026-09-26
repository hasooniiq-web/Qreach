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
            'plate_letter' => ['nullable', 'string', 'max:4', 'regex:/^[\p{L}]{1,4}$/u'],
            'plate_governorate' => ['nullable', 'string', 'max:40'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:100'],
            'plate_number' => ['sometimes', 'string', 'max:32'],
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
