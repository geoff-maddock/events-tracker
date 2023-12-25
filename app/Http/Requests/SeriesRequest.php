<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class SeriesRequest extends Request
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
            'name' => 'required|min:3|max:255',
            'slug' => Rule::unique('series')->ignore(isset($this->series) ? $this->series->id : ''),
            'short' => 'required|min:3|max:255',
            'event_type_id' => 'required',
            'visibility_id' => 'required',
            'presale_price' => 'nullable|numeric|between:0,999.99',
            'door_price' => 'nullable|numeric|between:0,999.99',
            'occurrence_type_id' => 'required',
            'primary_link' => ['nullable','regex:/^http:\/\/|https:\/\/|^$/','max:255'],
            'ticket_link' => ['nullable','regex:/^http:\/\/|https:\/\/|^$/','max:255'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'A series name is required',
            'name.min' => 'A series name must be at least 3 characters',
            'name.max' => 'A series name must be less than 255 characters',
            'slug.required' => 'A series slug is required',
            'slug.min' => 'A series slug must be at least 3 characters',
            'slug.max' => 'A series slug must be less than 255 characters',
            'short.required' => 'A series short description is required',
            'short.min' => 'A series short description must be at least 3 characters',
            'short.max' => 'A series short description must be less than 255 characters',
            'event_type_id.required' => 'An event type is required',
            'visibility_id.required' => 'A visibility is required',
            'occurrence_type_id.required' => 'An occurrence type is required',
            'primary_link.regex' => 'A primary link must be a valid URL starting with http:// or https:// or blank',
            'primary_link.max' => 'A primary link must be less than 255 characters',
            'ticket_link.regex' => 'A ticket link must be a valid URL starting with http:// or https:// or blank',
            'ticket_link.max' => 'A ticket link must be less than 255 characters',
        ];
    }
}
