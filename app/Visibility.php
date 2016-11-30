<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Visibility extends Eloquent {

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'name'
	];

	/**
	 * Additional fields to treat as Carbon instances.
	 *
	 * @var array
	 */
	protected $dates = [];

	
	/**
	 * A visibility can have many events
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function events()
	{
		return $this->hasMany('App\Event');
	}


}
