<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class EventStatus extends Eloquent {

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
	 * An event status can have many events
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function events()
	{
		return $this->hasMany('App\Event');
	}


}
