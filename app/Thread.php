<?php namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Thread extends Eloquent {


	public static function boot()
	{
		parent::boot();

		static::creating(function($thread)
		{
			$thread->created_by = Auth::user() ? Auth::user()->id : 1;
			$thread->updated_by = Auth::user() ? Auth::user()->id : 1;	
		});

		static::updating(function($thread)
		{
			$thread->updated_by = Auth::user() ? Auth::user()->id : 1;			
		});
	}

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
	'name', 
	'slug', 
	'description',
	'body',
	'thread_category_id',
	'visibility_id', 
	'forum_id',
	'views',
	];

	//protected $guarded = [];

	protected $dates = ['created_at','updated_at'];


	// building filter
	public function scopeFilter($query, QueryFilter $filters)
	{
		return $filters->apply($query);
	}


	public function path()
	{
		return '/threads/'. $this->id;
	}


	public function scopePast($query)
	{
		$query->where('created_at','<', Carbon::today()->startOfDay())
						->orderBy('start_at', 'desc');
	}


	/**
	 * Returns visible threads
	 *
	 */
	public function scopeVisible($query, $user)
	{

		$public = Visibility::where('name','=','Public')->first();
		
 		$query->where('visibility_id','=', $public ? $public->id : NULL )->orWhere('created_by','=',($user ? $user->id : NULL));

	}

	/**
	 * The posts that belong to the thread
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function posts()
	{
		return $this->hasMany('App\Post');
	}


	/**
	 * Add a post to a thread
	 *
	 */
	public function addPost($post)
	{
		$this->posts()->create($post);
	}

	/**
	 * Get the date of the last post
	 *
	 */
	public function getLastPostAtAttribute()
	{
		$post = $this->posts()->orderBy('created_at','desc')->first();

		if (isset($post))
		{
			return $post->created_at;
		} else {
			return $this->created_at;
		};

	}

	/**
	 * An thread is owned by a user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo('App\User','created_by');
	}

	/**
	 * An thread is created by one user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function creator()
	{
		return $this->belongsTo('App\User','created_by');
	}

	/**
	 * An thread is owned by one forum
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function forum()
	{
		return $this->belongsTo('App\Forum','forum_id');
	}

	/**
	 * An thread is created by one user
	 *
	 * @ param User $user
	 * 
	 * @ return boolean
	 */
	public function ownedBy(User $user)
	{
		return $this->created_by == $user->id;
	}


	/**
	 * An thread has one type
	 *
	 */
	public function threadCategory()
	{
		return $this->hasOne('App\ThreadCategory','id','thread_category_id');
	}


    /**
     * Get all of the threads photos
     */
    public function photos()
    {
		return $this->belongsToMany('App\Photo')->withTimestamps();
    }

	/**
	 * An thread has one visibility
	 *
	 */
	public function visibility()
	{
		return $this->hasOne('App\Visibility','id','visibility_id');
	}


	/**
	 * An thread has one series
	 *
	 */
	public function series()
	{
		return $this->belongsToMany('App\Series')->withTimestamps();
	}

	/**
	 * The tags that belong to the thread
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function tags()
	{
		return $this->belongsToMany('App\Tag')->withTimestamps();
	}


	/**
	 * The entities that belong to the thread
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function entities()
	{
		return $this->belongsToMany('App\Entity')->withTimestamps();
	}

	/**
	 * The events that belong to the thread
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function events()
	{
		return $this->belongsToMany('App\Event')->withTimestamps();
	}


	/**
	 * Get the count of users attending this thread
	 *
	 */
	public function getPostCountAttribute()
	{
		$posts = $this->posts()->get();

		return count($posts);
	}


	/**
	 * Get a list of tag ids associated with the thread
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getTagListAttribute()
	{
		return $this->tags->lists('id')->all();
	}

	/**
	 * Get a list of entity ids associated with the thread
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getEntityListAttribute()
	{
		return $this->entities->lists('id')->all();
	}


	/**
	 * Return a collection of threads with the passed tag
	 * 
	 * @return Collection $threads
	 * 
	 **/
	public static function getByTag($tag)
	{
		// get a list of threads that have the passed tag
		$threads = self::whereHas('tags', function($q) use ($tag)
		{
			$q->where('name','=', ucfirst($tag));
		});

		return $threads;
	}

	/**
	 * Return a collection of threads with the passed series
	 * 
	 * @return Collection $threads
	 * 
	 **/
	public static function getBySeries($tag)
	{
		// get a list of threads that have the passed series
		$threads = self::whereHas('series', function($q) use ($tag)
		{
			$q->where('slug','=', ucfirst($tag));
		});

		return $threads;
	}


	/**
	 * Return a collection of threads with the passed thread category
	 * 
	 * @return Collection $threads
	 * 
	 **/
	public static function getByCategory($slug)
	{
		// get a list of threads that have the passed category
		$threads = self::whereHas('threadCategory', function($q) use ($slug)
		{
			$q->where('name','=', $slug);
		})->orderBy('name','ASC');

		return $threads;
	}


	/**
	 * Return a collection of threads with the passed entity
	 * 
	 * @return Collection $threads
	 * 
	 **/
	public static function getByEntity($slug)
	{
		// get a list of threads that have the passed entity
		$threads = self::whereHas('entities', function($q) use ($slug)
		{
			$q->where('slug','=', $slug);
		});

		return $threads;
	}



	public function addPhoto(Photo $photo)
	{
		return $this->photos()->attach($photo->id);;
	}


	/**
	 * Return the flyer for this thread
	 * 
	 * @return Photo $photo
	 * 
	 **/
	public function getFlyer()
	{
		// get a list of threads that start on the passed date
		$flyer = $this->photos()->first();

		return $flyer;
	}

	/**
	 * Return the primary photo for this thread
	 * 
	 * @return Photo $photo
	 * 
	 **/
	public function getPrimaryPhoto()
	{
		// gets the first photo related to this thread
		$primary = $this->photos()->where('photos.is_primary','=','1')->first();

		return $primary;
	}

    /**
     * Create the slug from the name if none was passed
     */
    public function setSlugAttribute($value) {

        // grab the name and slugify it
        if ($value == '')
        {
        	$this->attributes['slug'] = str_slug($this->name);
        } else {
        	$this->attributes['slug'] = $value;
        }
    }
}
