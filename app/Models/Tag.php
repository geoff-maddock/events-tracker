<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use DateTime;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property TagType $tagType
 * @property DateTime $created_at
 */
class Tag extends Eloquent
{
    use HasFactory;

    /**
     * @var array
     *
     **/
    protected $fillable = [
        'name', 'tag_type_id',
    ];

    public function getRouteKeyName()
    {
        return 'id';
    }

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the threads that belong to the tag.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function threads()
    {
        return $this->belongsToMany(Thread::class)->withTimestamps();
    }

    /**
     * Get the series that belong to the tag.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function series()
    {
        return $this->belongsToMany(Series::class)->withTimestamps();
    }

    /**
     * Get the events that belong to the tag.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function events()
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /**
     * Get the entities that belong to the tag.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * A tag has one type.
     */
    public function tagType()
    {
        return $this->hasOne(TagType::class, 'id', 'tag_type_id');
    }

    /**
     * Checks if the tag is followed by the user.
     *
     * @return Follow $follows
     *
     **/
    public function followedBy($user)
    {
        $response = Follow::where('object_type', '=', 'tag')
        ->where('object_id', '=', $this->id)
        ->where('user_id', '=', $user->id)
        ->first();

        return $response;
    }

    /**
     * Returns the users that follow the tag.
     *
     * @return Collection $follows
     *
     **/
    public function followers()
    {
        $users = User::join('follows', 'users.id', '=', 'follows.user_id')
        ->where('follows.object_type', 'tag')
        ->where('follows.object_id', $this->id)
        ->get();

        return $users;
    }

    /**
     * The follows that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\Morph
     */
    public function follows()
    {
        return $this->morphMany(Follow::class, 'object', 'object_type', 'object_id');
    }

    /**
     * If there is a future event, return it.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function futureEvents()
    {
        $events = $this->events()->where('start_at', '>=', Carbon::now())->orderBy('start_at', 'ASC')->get();

        return $events;
    }

    /**
     * Return any events that match today for the start date.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function todaysEvents()
    {
        $events = $this->events()->whereDate('start_at', '=', Carbon::today()->toDateString())->orderBy('start_at', 'ASC')->get();

        return $events;
    }
}
