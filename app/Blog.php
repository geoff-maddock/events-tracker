<?php namespace App;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Blog extends Eloquent {


	public static function boot()
	{
		parent::boot();

		static::creating(function($blog)
		{
			//$blog->created_by = Auth::user() ? Auth::user()->id : 1;
			$blog->updated_by = Auth::user() ? Auth::user()->id : 1;	
		});

		static::updating(function($blog)
		{
			$blog->updated_by = Auth::user() ? Auth::user()->id : 1;			
		});
	}

	/**
	 * @var Array
	 *
	 **/

	protected $fillable = [
	'name',
	'slug',
	'description',
	'visibility_id',
	'body',
	'menu_id'
	];

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
		return '/blog/'. $this->id;
	}


	public function scopePast($query)
	{
		$query->where('created_at','<', Carbon::today()->startOfDay())
						->orderBy('start_at', 'desc');
	}


	/**
	 * Returns visible blogs
	 *
	 */
	public function scopeVisible($query, $user)
	{

		$public = Visibility::where('name','=','Public')->first();
		
 		$query->where('visibility_id','=', $public ? $public->id : NULL )->orWhere('created_by','=',($user ? $user->id : NULL));

	}


	/**
	 * An blog is owned by a user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo('App\User','created_by');
	}


    /**
     * The likes that belong to the blog
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likes()
    {
        return $this->morphMany('App\Like','object', 'object_type', 'object_id');
    }

	/**
	 * An blog is owned by a menu
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function menu()
	{
		return $this->belongsTo('App\Menu','menu_id');
	}

	/**
	 * An blog is created by one user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function creator()
	{
		return $this->belongsTo('App\User','created_by');
	}

	/**
	 * An blog is created by one user
	 *
	 * @ param User $user
	 * 
	 * @ return boolean
	 */
	public function ownedBy(User $user)
	{
		return $this->created_by == $user->id;
	}


//    /**
//     * Get all of the blogs photos
//     */
//    public function photos()
//    {
//		return $this->belongsToMany('App\Photo')->withTimestamps();
//    }

	/**
	 * An blog has one visibility
	 *
	 */
	public function visibility()
	{
		return $this->hasOne('App\Visibility','id','visibility_id');
	}

	/**
	 * The tags that belong to the blog
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function tags()
	{
		return $this->belongsToMany('App\Tag')->withTimestamps();
	}


	/**
	 * The entities that belong to the blog
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function entities()
	{
		return $this->belongsToMany('App\Entity')->withTimestamps();
	}

	// TODO relate blogs to events
//	/**
//	 * The events that belong to the blog
//	 *
//	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
//	 */
//	public function events()
//	{
//		return $this->belongsToMany('App\Event')->withTimestamps();
//	}


	/**
	 * Get a list of tag ids associated with the blog
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getTagListAttribute()
	{
		return $this->tags->pluck('id')->all();
	}

	/**
	 * Get a list of entity ids associated with the blog
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getEntityListAttribute()
	{
		return $this->entities->pluck('id')->all();
	}


	/**
	 * Return a collection of blogs with the passed tag
	 * 
	 * @return Collection $blogs
	 * 
	 **/
	public static function getByTag($tag)
	{
		// get a list of blogs that have the passed tag
		$blogs = self::whereHas('tags', function($q) use ($tag)
		{
			$q->where('name','=', ucfirst($tag));
		});

		return $blogs;
	}


	/**
	 * Return a collection of blogs with the passed entity
	 * 
	 * @return Collection $blogs
	 * 
	 **/
	public static function getByEntity($slug)
	{
		// get a list of blogs that have the passed entity
		$blogs = self::whereHas('entities', function($q) use ($slug)
		{
			$q->where('slug','=', $slug);
		});

		return $blogs;
	}


	public function addPhoto(Photo $photo)
	{
		return $this->photos()->attach($photo->id);;
	}


	/**
	 * Return the primary photo for this blog
	 * 
	 * @return Photo $photo
	 * 
	 **/
	public function getPrimaryPhoto()
	{
		// gets the first photo related to this blog
		$primary = $this->photos()->where('photos.is_primary','=','1')->first();

		return $primary;
	}

	/**
	 * Checks if a blog was recent - thus edittable or deletable
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
     * Determine if the blog was just published a moment ago.
     *
     * @return bool
     */
    public function wasJustPublished()
    {
        return $this->created_at->gt(Carbon::now()->subMinute());
    }

    /**
     * Fetch all mentioned users within the blog's body.
     *
     * @return array
     */
    public function mentionedUsers()
    {
        preg_match_all('/@([\w\-]+)/', $this->body, $matches);
        return $matches[1];
    }


    /**
     * Checks if the blog is liked by the user
     *
     * @return Collection $likes
     *
     **/
    public function likedBy($user)
    {
        $response = Like::where('object_type','=', 'blog')
            ->where('object_id','=',$this->id)
            ->where('user_id', '=', $user->id)
            ->first();
        // return any like instances

        return $response;
    }


    /**
     * Returns the users that like the blog
     *
     * @return Collection $likes
     *
     **/
    public function likers()
    {
        $users = User::join('likes', 'users.id', '=', 'likes.user_id')
            ->where('likes.object_type', 'blog')
            ->where('likes.object_id', $this->id)
            ->get();

        return $users;
    }
}
