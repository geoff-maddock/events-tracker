<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class EventReviewRequest extends Request
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
            'review' => 'required|min:3',
            'review_type_id' => 'required',
            'expectation' => 'nullable|integer|min:0|max:10',
            'rating' => 'nullable|integer|min:0|max:10',
            'attended' => 'nullable',
            'confirmed' => 'nullable',
        ];
    }
}
