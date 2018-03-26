<?php namespace App;

use Carbon\Carbon;
use App\User;
use App\Action;
use Illuminate\Support\Facades\Request;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Activity extends Eloquent {


    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'object_table', 'object_name', 'object_id'
	];


	protected $dates = ['created_at', 'updated_at'];


	/**
	 * Get the events that belong to the activity
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function events()
	{
		return $this->belongsToMany('App\Event')->withTimestamps();
	}

	/**
	 * Get the entities that belong to the activity
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function entities()
	{
		return $this->belongsToMany('App\Entity')->withTimestamps();
	}

	/**
	 * An activity is owned by a user
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo('App\User','user_id');
	}

	/**
	 * Get the style of an activity
	 */
	public function getStyleAttribute()
	{
		if ($this->action_id == 3)
		{
			return 'list-group-item-warning';
		};

		return '';
	}


    /**
     * Get a link to show the activity
     */
    public function getShowLink()
    {
        if ($this->object_table == 'Tag')
        {
            $tag = Tag::find($this->object_id);
            return '/'.str_plural($this->object_table).'/'.$tag->name;
        };

        if ($this->object_table == 'Entity')
        {
            $entity = Entity::find($this->object_id);
            return '/'.str_plural($this->object_table).'/'.$entity->slug;
        };
        return '/'.str_plural($this->object_table).'/'.$this->object_id;
    }

	/**
	 * Get the age of the activity
	 */
	public function getAgeAttribute()
	{
		return $this->created_at->diffForHumans();
	}

	/**
	 * Get the name of the user
	 */
	public function getUserNameAttribute()
	{
		return $this->user ? $this->user->name : 'unknown';
	}


	public static function log($object, $user, $action, $message = NULL)
	{
		$class = get_class($object);

		// get the action id if it's not an integer
		if (!is_int($action))
		{
			$act = Action::where('name', '=', $action)->first();
			$a = $act ? $act->id : NULL;
		} else {
			$act = Action::findOrFail($action);
			$a = $action;
		}

		// convert class into table
		$split = explode('\\', $class);
		$table = $split[1] ? $split[1] : $class;

		// log the activity here
		$activity = new Activity();
		$activity->user_id = $user ? $user->id : 1;
		$activity->object_table = $table;
		$activity->object_id = $object->id;
		$activity->action_id = $a;
		$activity->object_name = $object->name;
		$activity->changes = $object;
		$activity->ip_address = \Request::ip();

		if ($message)
		{
			$activity->message = sprintf("Created user %s", $user->email);
		} else {
			// otherwise build message
			$m = $act->name.' '.strtolower($table).' '.$object->name;
			$activity->message = $m;
		}
		$activity->save();
	}
}
