<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Blog;
use Illuminate\Validation\Rule;

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
        $routeBlog = $this->route('blog');
        $blogId = $routeBlog instanceof Blog ? $routeBlog->id : null;

        return [
            'name' => 'required|min:3|max:255',
            'slug' => [
                'required', 'min:3', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('blogs', 'slug')->ignore($blogId),
            ],
            'body' => 'required|min:3|max:65535',
            'visibility_id' => 'required|integer',
            'content_type_id' => 'required|integer',
        ];
    }
}
