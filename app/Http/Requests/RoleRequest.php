<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class RoleRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id ?? null;

        return [
            'name' => 'required|min:3|max:255',
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'slug')->ignore($roleId),
            ],
            'short' => 'nullable|max:255',
        ];
    }
}
