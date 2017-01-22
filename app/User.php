<?php namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

	use Authenticatable, CanResetPassword;

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
	 * A user can have one profile()
	 *
	 */
	public function profile()
	{
		return $this->hasOne('App\Profile');
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
	 * Events that were created by the user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function createdEvents()
	{
		$events = $this->events()->where('created_at','=',Auth::user())->orderBy('start_at', 'ASC')->get();
		return $events;
	}	
}
