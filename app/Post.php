<?php namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Post extends Eloquent {


	public static function boot()
	{
		parent::boot();

		static::creating(function($post)
		{
			//$post->created_by = Auth::user() ? Auth::user()->id : 1;
			$post->updated_by = Auth::user() ? Auth::user()->id : 1;	
		});

		static::updating(function($post)
		{
			$post->updated_by = Auth::user() ? Auth::user()->id : 1;			
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
	/*
	protected $fillable = [
	'name', 
	'slug', 
	'description',
	'visibility_id', 
	'body',
	'thread_id'
	];
	*/
	protected $guarded = [];
	protected $dates = ['created_at','updated_at'];

	public function __toString()
	{
		return (string) $this->body;
	}

	// building filter
	public function scopeFilter($query, QueryFilter $filters)
	{
		return $filters->apply($query);
	}


	public function path()
	{
		return '/post/'. $this->id;
	}


	public function scopePast($query)
	{
		$query->where('created_at','<', Carbon::today()->startOfDay())
						->orderBy('start_at', 'desc');
	}


	/**
	 * Returns visible posts
	 *
	 */
	public function scopeVisible($query, $user)
	{

		$public = Visibility::where('name','=','Public')->first();
		
 		$query->where('visibility_id','=', $public ? $public->id : NULL )->orWhere('created_by','=',($user ? $user->id : NULL));

	}


	/**
	 * An post is owned by a user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo('App\User','created_by');
	}


    /**
     * The likes that belong to the post
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likes()
    {
        return $this->morphMany('App\Like','object', 'object_type', 'object_id');
    }

	/**
	 * An post is owned by a thread
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function thread()
	{
		return $this->belongsTo('App\Thread','thread_id');
	}

	/**
	 * An post is created by one user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function creator()
	{
		return $this->belongsTo('App\User','created_by');
	}

	/**
	 * An post is created by one user
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
     * Get all of the posts photos
     */
    public function photos()
    {
		return $this->belongsToMany('App\Photo')->withTimestamps();
    }

	/**
	 * An post has one visibility
	 *
	 */
	public function visibility()
	{
		return $this->hasOne('App\Visibility','id','visibility_id');
	}

	/**
	 * The tags that belong to the post
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function tags()
	{
		return $this->belongsToMany('App\Tag')->withTimestamps();
	}


	/**
	 * The entities that belong to the post
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function entities()
	{
		return $this->belongsToMany('App\Entity')->withTimestamps();
	}

	/**
	 * The events that belong to the post
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function events()
	{
		return $this->belongsToMany('App\Event')->withTimestamps();
	}



	/**
	 * Get a list of tag ids associated with the post
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getTagListAttribute()
	{
		return $this->tags->pluck('id')->all();
	}

	/**
	 * Get a list of entity ids associated with the post
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getEntityListAttribute()
	{
		return $this->entities->pluck('id')->all();
	}


	/**
	 * Return a collection of posts with the passed tag
	 * 
	 * @return Collection $posts
	 * 
	 **/
	public static function getByTag($tag)
	{
		// get a list of posts that have the passed tag
		$posts = self::whereHas('tags', function($q) use ($tag)
		{
			$q->where('name','=', ucfirst($tag));
		});

		return $posts;
	}


	/**
	 * Return a collection of posts with the passed entity
	 * 
	 * @return Collection $posts
	 * 
	 **/
	public static function getByEntity($slug)
	{
		// get a list of posts that have the passed entity
		$posts = self::whereHas('entities', function($q) use ($slug)
		{
			$q->where('slug','=', $slug);
		});

		return $posts;
	}


	public function addPhoto(Photo $photo)
	{
		return $this->photos()->attach($photo->id);;
	}


	/**
	 * Return the primary photo for this post
	 * 
	 * @return Photo $photo
	 * 
	 **/
	public function getPrimaryPhoto()
	{
		// gets the first photo related to this post
		$primary = $this->photos()->where('photos.is_primary','=','1')->first();

		return $primary;
	}

	/**
	 * Checks if a post was recent - thus edittable or deletable
	 *
	 * 
	 * @ return boolean
	 */
	public function isRecent()
	{
		$recent_hours = 24;

		// recency cut off date
		$recent_date = Carbon::parse('now')->subHours($recent_hours);

		$created_date = Carbon::parse($this->created_at);

		return ($created_date > $recent_date) ? true : false;
	}


    /**
     * Determine if the post was just published a moment ago.
     *
     * @return bool
     */
    public function wasJustPublished()
    {
        return $this->created_at->gt(Carbon::now()->subMinute());
    }

    /**
     * Fetch all mentioned users within the post's body.
     *
     * @return array
     */
    public function mentionedUsers()
    {
        preg_match_all('/@([\w\-]+)/', $this->body, $matches);
        return $matches[1];
    }


    /**
     * Checks if the post is liked by the user
     *
     * @return Collection $likes
     *
     **/
    public function likedBy($user)
    {
        $response = Like::where('object_type','=', 'post')
            ->where('object_id','=',$this->id)
            ->where('user_id', '=', $user->id)
            ->first();
        // return any like instances

        return $response;
    }


    /**
     * Returns the users that like the post
     *
     * @return Collection $likes
     *
     **/
    public function likers()
    {
        $users = User::join('likes', 'users.id', '=', 'likes.user_id')
            ->where('likes.object_type', 'post')
            ->where('likes.object_id', $this->id)
            ->get();

        return $users;
    }
}
