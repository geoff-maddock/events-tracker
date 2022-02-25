<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * App\Models\Activity.
 *
 * @property int                                                           $object_id
 * @property string                                                        $object_table
 * @property datetime                                                      $created_at
 * @property int                                                           $id
 * @property int|null                                                      $user_id
 * @property string|null                                                   $object_name
 * @property string|null                                                   $child_object_table
 * @property string|null                                                   $child_object_name
 * @property int|null                                                      $child_object_id
 * @property string|null                                                   $message
 * @property string|null                                                   $changes
 * @property int|null                                                      $action_id
 * @property \Illuminate\Support\Carbon|null                               $updated_at
 * @property string|null                                                   $ip_address
 * @property \App\Models\Action|null                                       $action
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property int|null                                                      $entities_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Event[]  $events
 * @property int|null                                                      $events_count
 * @property mixed                                                         $age
 * @property mixed                                                         $style
 * @property mixed                                                         $user_name
 * @property \App\Models\User|null                                         $user
 *
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
    public const DEFAULT_SHOW_COUNT = 100;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'object_table', 'object_name', 'object_id',
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the events that belong to the activity.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Event')->withTimestamps();
    }

    /**
     * Get the entities that belong to the activity.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Entity')->withTimestamps();
    }

    /**
     * An activity is owned by a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /**
     * An activity has one action.
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo('App\Models\Action', 'action_id');
    }

    /**
     * Get the style of an activity.
     */
    public function getStyleAttribute(): string
    {
        if (null !== $this->action && $this->action->name === 'Delete') {
            return 'list-group-item-warning';
        }

        return '';
    }

    /**
     * Get a link to show the activity.
     */
    public function getShowLink(): string
    {
        if ('Tag' === $this->object_table) {
            if ($tag = Tag::find($this->object_id)) {
                return '/'.Str::plural($this->object_table).'/'.$tag->name;
            }
        }

        if ('Entity' === $this->object_table) {
            if ($entity = Entity::find($this->object_id)) {
                return '/'.Str::plural($this->object_table).'/'.$entity->slug;
            }
        }

        if ($this->object_table) {
            return '/'.Str::plural($this->object_table).'/'.$this->object_id;
        }

        return '/';
    }

    /**
     * Get the age of the activity.
     */
    public function getAgeAttribute(): string
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    /**
     * Get the name of the user.
     */
    public function getUserNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'unknown';
    }

    public static function log(mixed $object, ?User $user, mixed $action, string $message = null): void
    {
        $class = get_class($object);

        // get the action id if it's not an integer
        if (!is_int($action)) {
            $act = Action::where('name', '=', $action)->first();
            $a = $act ? $act->id : null;
        } else {
            // if this action can't be found, it fails with a 404
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
            $m = $act->name.' '.strtolower($table).' '.$object->name;
            $activity->message = $m;
        }

        $activity->save();
    }
}
