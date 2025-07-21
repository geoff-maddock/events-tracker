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
        $userId = $this->route('user') ? $this->route('user')->id : null;

        return [
            'name' => ['required','min:6','max:255', 'regex:/^[a-zA-Z0-9\s._-]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['required', 'min:8', 'max:60'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'A user name is required',
            'name.min' => 'A user name must be at least 6 characters',
            'name.max' => 'A user name must be less than 255 characters',
            'name.regex' => 'A user name must contain only letters, numbers, spaces, periods, underscores, and dashes',

            'email.required' => 'An email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.max' => 'Email address must be less than 255 characters',
            'email.unique' => 'This email address is already registered',

            'password.required' => 'A password is required',
            'password.min' => 'A password must be at least 8 characters',
            'password.max' => 'A password must be less than 60 characters',
        ];
    }
 }