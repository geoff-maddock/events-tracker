<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class BlogRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3|max:255',
            'slug' => 'required|min:3|max:255|regex:/^[a-z0-9-]+$/|unique:blogs,slug',
            'body' => 'required|min:3|max:65535',
            'visibility_id' => 'required|integer',
            'content_type_id' => 'required|integer',
        ];
    }
}
