<?php 

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Location;

class LocationRequest extends Request {

	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		return Location::where([
			'created_by' => $this->user()->id
			])->exists();
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
			'city' => 'required|min:3',
            'visibility_id' => 'required'
		];
	}

}
