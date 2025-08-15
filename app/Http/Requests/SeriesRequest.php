<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class SeriesRequest extends Request
{
   // Define constant for the occurrence type
    const OCCURRENCE_TYPE_NO_SCHEDULE = 1;
    const OCCURRENCE_TYPE_WEEKLY = 2;
    const OCCURRENCE_TYPE_BIWEEKLY = 3;
    const OCCURRENCE_TYPE_MONTHLY = 4;
    const OCCURRENCE_TYPE_BIMONTHLY = 5;
    
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
        $seriesSlug = $this->route('series')?->id ?? null;

        $rules = [
            'name' => 'required|min:3|max:255',
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('series', 'slug')->ignore($seriesSlug),
            ],
            'short' => 'required|min:3|max:255',
            'length' => 'integer',
            'event_type_id' => 'required',
            'visibility_id' => 'required',
            'presale_price' => 'nullable|numeric|between:0,999.99',
            'door_price' => 'nullable|numeric|between:0,999.99',
            'occurrence_type_id' => 'required',
            'primary_link' => ['nullable','regex:/^http:\/\/|https:\/\/|^$/','max:255'],
            'ticket_link' => ['nullable','regex:/^http:\/\/|https:\/\/|^$/','max:255'],
            'occurrence_week_id' => 'nullable',
            'occurrence_day_id' => 'nullable',
        ];
        
        // Add conditional validation rules for monthly occurrence type
        if ($this->input('occurrence_type_id') == self::OCCURRENCE_TYPE_MONTHLY ) {
            $rules['occurrence_week_id'] = 'required';
            $rules['occurrence_day_id'] = 'required';
        }
        
        if ($this->input('occurrence_type_id') == self::OCCURRENCE_TYPE_BIMONTHLY ) {
            $rules['occurrence_week_id'] = 'required';
            $rules['occurrence_day_id'] = 'required';
        }

        if ($this->input('occurrence_type_id') == self::OCCURRENCE_TYPE_WEEKLY ) {
            $rules['occurrence_day_id'] = 'required';
        }

        if ($this->input('occurrence_type_id') == self::OCCURRENCE_TYPE_BIWEEKLY ) {
            $rules['occurrence_day_id'] = 'required';
        }

        return $rules;
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
            'occurrence_week_id.required' => 'An occurrence week is required for this occurrence type',
            'occurrence_day_id.required' => 'An occurrence day is required for this occurrence type',
            'primary_link.regex' => 'A primary link must be a valid URL starting with http:// or https:// or blank',
            'primary_link.max' => 'A primary link must be less than 255 characters',
            'ticket_link.regex' => 'A ticket link must be a valid URL starting with http:// or https:// or blank',
            'ticket_link.max' => 'A ticket link must be less than 255 characters',
            'length.integer' => 'A series length must be an integer',
        ];
    }
}
