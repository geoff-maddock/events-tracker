<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UserRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;  // we have no users yet
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required','min:3','max:255', 'regex:/^[a-zA-Z0-9\s._-]+$/'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'A user name is required',
            'name.min' => 'A user name must be at least 3 characters',
            'name.max' => 'A user name must be less than 255 characters',
            'name.regex' => 'A user name must contain only letters, numbers, spaces, periods, underscores, and dashes',
        ];
    }
 }