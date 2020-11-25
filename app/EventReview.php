<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Builder;
//use App\Http\Controllers\UploadedFile;

class EventReview extends Eloquent {


    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    //protected $dateFormat = 'Y-m-d\\TH:i';

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'event_id','user_id','review_type_id','attended','confirmed','expectation','rating','review'
	];

 
	protected $dates = ['created_at','updated_at'];


	/**
	 * Get the event that the review belongs to
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function event()
	{
		return $this->belongsTo('App\Event');
	}

	/**
	 * Get the user that the response belongs to
	 *
	 */
	public function user()
	{
		return $this->hasOne('App\User');
	}


    /**
     * An review is created by one user
     *
     * @ param User $user
     *
     * @ return boolean
     */
    public function ownedBy(User $user)
    {
        return $this->user_id == $user->id;
    }

	/**
	 * Get the response type that the response belongs to
	 *
	 */
	public function reviewType()
	{
		return $this->belongsTo('App\ReviewType');;
	}

	
	public function scopeFuture(Builder $query): Builder
    {
        return $query->where('start_at', '>=', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'asc');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_at', '<', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'desc');
    }
}
