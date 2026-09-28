<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Group;

class GroupRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() !== null && $this->user()->can('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3',
            'label' => 'required|min:3',
            'level' => 'required',
            'description' => 'nullable|string',
            'permission_list' => 'nullable|array',
            'permission_list.*' => 'integer|exists:permissions,id',
            'user_list' => 'nullable|array',
            'user_list.*' => 'integer|exists:users,id',
        ];
    }
}
