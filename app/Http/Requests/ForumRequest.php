<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Forum;

class ForumRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // forums are site structure: only admins may create or change one. Checked here so a
        // non-admin gets 403 before validation instead of a 422 for a bad payload.
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
            'slug' => 'required|min:3|regex:/^[a-z0-9-]+$/',
            'visibility_id' => 'required',
        ];
    }
}
