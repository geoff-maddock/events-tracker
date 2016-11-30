<?php namespace App;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Song extends Eloquent {

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'title','lyrics','slug'
	];

}
