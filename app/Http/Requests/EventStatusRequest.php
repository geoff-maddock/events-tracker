<?php

namespace App\Http\Requests;

class EventStatusRequest extends Request
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|min:3|max:255|unique:event_statuses,name',
        ];
    }
}
