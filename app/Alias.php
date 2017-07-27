<?php namespace App;

use Carbon\Carbon;
use App\User;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Alias extends Eloquent {


    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d\\TH:i';

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'name', 
	];


	protected $dates = ['created_at','updated_at'];



	/**
	 * Get the entities that belong to the alias
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function entities()
	{
		return $this->belongsToMany('App\Entity')->withTimestamps();
	}


}
