<?php namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Profile extends Eloquent {


	public static function boot()
	{
		parent::boot();

/*
		static::creating(function($profile)
		{
			if (Auth::user())
			{
				$profile->created_by = Auth::user()->id;
				$profile->updated_by = Auth::user()->id;
			};
		});

		static::updating(function($profile)
		{
			$profile->updated_by = Auth::user()->id;			
		});
		*/
	}

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'first_name','last_name', 'bio', 'alias', 'location', 'facebook_username', 'twitter_username', 'default_theme'
	];


	protected $dates = ['updated_at'];


	/**
	 * An profile is owned by a user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo('App\User', 'user_id');
	}


	/**
	 * The links that belong to the entity
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function links()
	{
		return $this->belongsToMany('App\Link');
	}

	

    /**
     * Get all of the entities photos
     */
    public function photos()
    {
		return $this->belongsToMany('App\Photo')->withTimestamps();
    }



}
