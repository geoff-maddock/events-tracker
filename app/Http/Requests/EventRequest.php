<?php 

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Event;

class EventRequest extends Request {

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
			'slug' => 'unique:events'.(isset($this->events) ? ',id,'.$this->events->id : ''),
			'start_at' => 'required|date',
			'event_type_id' => 'required',
			'visibility_id' => 'required'
		];
	}

}
