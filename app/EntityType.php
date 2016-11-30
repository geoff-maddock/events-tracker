<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class EntityType extends Eloquent {

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'name'
	];


	protected $dates = ['updated_at'];

}
