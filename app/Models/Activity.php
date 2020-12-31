<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * App\Models\Activity
 *
 * @property int $object_id
 * @property string $object_table
 * @property datetime $created_at
 * @property int $id
 * @property int|null $user_id
 * @property string|null $object_name
 * @property string|null $child_object_table
 * @property string|null $child_object_name
 * @property int|null $child_object_id
 * @property string|null $message
 * @property string|null $changes
 * @property int|null $action_id
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $ip_address
 * @property-read \App\Models\Action|null $action
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property-read int|null $entities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Event[] $events
 * @property-read int|null $events_count
 * @property-read mixed $age
 * @property-read mixed $style
 * @property-read mixed $user_name
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereActionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereChildObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereChildObjectName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereChildObjectTable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereObjectName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereObjectTable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereUserId($value)
 * @mixin \Eloquent
 */
class Activity extends Eloquent
{
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * @var array
     *
     **/
    protected $fillable = [
        'object_table', 'object_name', 'object_id',
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the events that belong to the activity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function events()
    {
        return $this->belongsToMany('App\Models\Event')->withTimestamps();
    }

    /**
     * Get the entities that belong to the activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany('App\Models\Entity')->withTimestamps();
    }

    /**
     * An activity is owned by a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /**
     * An activity has one action.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo('App\Models\Action', 'action_id');
    }

    /**
     * Get the style of an activity.
     */
    public function getStyleAttribute()
    {
        if (null !== $this->action && $this->action->name === 'Delete') {
            return 'list-group-item-warning';
        }

        return '';
    }

    /**
     * Get a link to show the activity.
     */
    public function getShowLink()
    {
        if ('Tag' === $this->object_table) {
            if ($tag = Tag::find($this->object_id)) {
                return '/' . Str::plural($this->object_table) . '/' . $tag->name;
            }
        }

        if ('Entity' === $this->object_table) {
            if ($entity = Entity::find($this->object_id)) {
                return '/' . Str::plural($this->object_table) . '/' . $entity->slug;
            }
        }

        if ($this->object_table) {
            return '/' . Str::plural($this->object_table) . '/' . $this->object_id;
        }

        return '/';
    }

    /**
     * Get the age of the activity.
     */
    public function getAgeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the name of the user.
     */
    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'unknown';
    }

    public static function log($object, $user, $action, $message = null)
    {
        $class = get_class($object);

        // get the action id if it's not an integer
        if (!is_int($action)) {
            $act = Action::where('name', '=', $action)->first();
            $a = $act ? $act->id : null;
        } else {
            $act = Action::findOrFail($action);
            $a = $action;
        }

        // convert entity class into table
        $split = explode('\\', $class);
        $table = isset($split[2]) ? $split[2] : $class;

        // log the activity here
        $activity = new Activity();
        $activity->user_id = $user ? $user->id : 1;
        $activity->object_table = $table;
        $activity->object_id = $object->id;
        $activity->action_id = $a;
        $activity->object_name = $object->name;
        $activity->changes = $object;
        $activity->ip_address = \Request::ip();

        if ($message) {
            $activity->message = sprintf($message);
        } else {
            // otherwise build message
            $m = $act->name . ' ' . strtolower($table) . ' ' . $object->name;
            $activity->message = $m;
        }

        $activity->save();
    }
}
