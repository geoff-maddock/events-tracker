<?php namespace App\Http\Requests;

use App\Http\Requests\Request;

class ProfileRequest extends Request {

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
			//'name' => 'required|min:3',
		];
	}

}
