<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Location extends Eloquent {


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
	'name', 'slug', 'attn', 'address_one', 'address_two', 'neighborhood', 'city', 'state', 'postcode', 'country', 'latitude', 'longitude', 'location_type_id', 'visibility_id', 'entity_id', 'capacity','map_url'
	];

 
	protected $dates = ['created_at','updated_at'];

	
	/**
	 * Get the entities that belong to the tag
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function entities()
	{
		return $this->belongsToOne('App\Entity')->withTimestamps();
	}

	/**
	 * A location has one type
	 *
	 */
	public function locationType()
	{
		return $this->hasOne('App\LocationType','id','location_type_id');
	}

	/**
	 * A location has one visibility
	 *
	 */
	public function visibility()
	{
		return $this->hasOne('App\Visibility','id','visibility_id');
	}


    /**
     * Returns visible events
     *
     */
    public function scopeVisible($query, $user)
    {

        $public = Visibility::where('name','=','Public')->first();
        $guarded = Visibility::where('name','=','Guarded')->first();

        $query->where('visibility_id','=', $public ? $public->id : NULL )->orWhere('created_by','=',($user ? $user->id : NULL));
        /*
        $query->orWhere(function ($query) {
        	$query->where('visibility_id','=', $guarded ? $guarded->id : NULL );
        });
		*/
    }
}
