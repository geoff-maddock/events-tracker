<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class AdminPasswordResetRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() && $this->user()->can('grant_access');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'password' => ['required', 'min:8', 'max:60', 'confirmed'],
        ];
    }

    public function messages()
    {
        return [
            'password.required' => 'A password is required',
            'password.min' => 'A password must be at least 8 characters',
            'password.max' => 'A password must be less than 60 characters',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}
