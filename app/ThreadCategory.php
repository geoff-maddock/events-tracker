<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ThreadCategory extends Eloquent {

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
	 * A thread category can have many threads
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function threads()
	{
		return $this->hasMany('App\Thread');
	}

	public function getRouteKeyName()
    {
        return 'id';
    }


}
