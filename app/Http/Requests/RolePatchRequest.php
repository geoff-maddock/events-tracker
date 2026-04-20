<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class RolePatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'slug')->ignore($roleId),
            ],
            'short' => ['sometimes', 'nullable', 'max:255'],
        ];
    }
}
