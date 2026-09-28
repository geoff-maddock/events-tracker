<?php

namespace App\Http\Requests;

class ContactRequest extends Request
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
            'name' => 'required|min:3',
            'type' => 'required|min:3',
            'visibility_id' => 'required',
            'email' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'other' => 'nullable|string|max:255',
        ];
    }
}
