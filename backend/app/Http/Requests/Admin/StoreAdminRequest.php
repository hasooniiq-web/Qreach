<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:admins,email'],
            // Same minimum as the self-service password change.
            'password' => ['required', 'string', 'min:12', 'max:128'],
            'must_change_password' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
        ];
    }

    public function attributes(): array
    {
        return ['password' => 'كلمة المرور'];
    }
}
