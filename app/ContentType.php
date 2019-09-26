<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ContentType extends Eloquent {

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


    public function __toString()
    {
        return (string) $this->name;
    }

}
