<?php namespace App;

use DB;
use App\EventResponse;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract {

	use Authenticatable, Authorizable, CanResetPassword;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['name', 'email', 'password'];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = ['password', 'remember_token'];


	/**
	 * A user can have many events
	 *
	 */
	public function events()
	{
		return $this->hasMany('App\Event', 'created_by')->orderBy('start_at', 'DESC');
	}

	/**
	 * A how many events the user created
	 *
	 */
	public function getEventCountAttribute()
	{
		return $this->hasMany('App\Event', 'created_by')->count();
	}

	/**
	 * A user can have many series
	 *
	 */
	public function series()
	{
		return $this->hasMany('App\Series');
	}

	/**
	 * A user can have many comments
	 *
	 */
	public function comments()
	{
		return $this->hasMany('App\Comment');
	}

	/**
	 * A user can have many event responses
	 *
	 */
	public function eventResponses()
	{
		return $this->hasMany('App\EventResponse');
	}

	/**
	 * A user can follow many objects
	 *
	 */
	public function follows()
	{
		return $this->hasMany('App\Follow');
	}

	/**
	 * A user can have one profile()
	 *
	 */
	public function profile()
	{
		return $this->hasOne('App\Profile');
	}

    /**
     * Get all of the events photos
     */
    public function photos()
    {
		return $this->belongsToMany('App\Photo')->withTimestamps();
    }

	/**
	 * Return the primary photo for this user
	 * 
	 * @return Photo $photo
	 * 
	 **/
	public function getPrimaryPhoto()
	{
		// get a list of events that start on the passed date
		$primary = $this->photos()->where('photos.is_primary','=','1')->first();

		return $primary;
	}

	/**
	 * Return the count of events the user is attending
	 *
	 */
	public function getAttendingCountAttribute()
	{
		$responses = $this->eventResponses()->get();
		$responses->filter(function($e)
		{
			return ($e->responseType->name == 'Attending');
		});

		return count($responses);
	}

	/**
	 * Return the count of entities the user is following
	 *
	 */
	public function getEntitiesFollowingCountAttribute()
	{
		$responses = $this->follows()->get();
		$responses->filter(function($e)
		{
			return ($e->object_type == 'entity');
		});

		return count($responses);
	}

	/**
	 * Return a list of events the user is attending in the future
	 *
	 */
	public function getAttendingFuture()
	{
		$events = Event::join('event_responses', 'events.id', '=', 'event_responses.event_id')
			->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
			->where('response_types.name', '=', 'Attending')
			->where('event_responses.user_id', '=', $this->id)
			->where('start_at','>=', Carbon::today()->startOfDay())
			->orderBy('events.start_at','asc')
			->select('events.*')
			->get();
		return $events;
	}

	/**
	 * Return a list of events the user is attending
	 *
	 */
	public function getAttending()
	{
		$events = Event::join('event_responses', 'events.id', '=', 'event_responses.event_id')
			->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
			->where('response_types.name', '=', 'Attending')
			->where('event_responses.user_id', '=', $this->id)
			->orderBy('events.start_at','desc')
			->select('events.*')
			->get();
		return $events;
	}


	/**
	 * Return a list of events the user is attending
	 *
	 */
	public function getEntitiesFollowing()
	{
		$entities = Entity::join('follows', 'entities.id', '=', 'follows.object_id')
			->where('follows.object_type', '=', 'entity')
			->where('follows.user_id', '=', $this->id)
			->orderBy('follows.created_at','desc')
			->select('entities.*')
			->get();
		return $entities;
	
	}

	/**
	 * Events that were created by the user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function createdEvents()
	{
		$events = $this->events()->where('created_at','=',Auth::user())->orderBy('start_at', 'ASC')->get();
		return $events;
	}	


	public function addPhoto(Photo $photo)
	{
		return $this->photos()->attach($photo->id);;
	}

}
