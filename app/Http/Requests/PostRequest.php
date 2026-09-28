<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Post;

class PostRequest extends Request
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
        // no thread_id: a post stays in its thread. The edit form sends it as a hidden
        // field, and accepting it let an author move a post into any thread (#2180)
        return [
            'body' => 'required|min:3',
            'visibility_id' => 'required',
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
