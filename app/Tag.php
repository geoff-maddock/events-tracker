<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Tag extends Eloquent {


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
	'name', 'tag_type_id'
	];


	protected $dates = ['created_at','updated_at'];

	

	/**
	 * Get the events that belong to the tag
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function events()
	{
		return $this->belongsToMany('App\Event')->withTimestamps();
	}

	/**
	 * Get the entities that belong to the tag
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function entities()
	{
		return $this->belongsToMany('App\Entity')->withTimestamps();
	}

	/**
	 * A tag has one type
	 *
	 */
	public function tagType()
	{
		return $this->hasOne('App\TagType','id','tag_type_id');
	}



}
