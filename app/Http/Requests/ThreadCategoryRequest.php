<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ThreadCategoryRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // site configuration is admin-only; checked here so non-admins get 403 before validation
        return $this->user() !== null && $this->user()->isAdmin();
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
            'forum_id' => 'required',
        ];
    }
}
