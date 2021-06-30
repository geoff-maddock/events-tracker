<?php

namespace App\Models;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * App\Models\Profile
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $bio
 * @property string|null $alias
 * @property string|null $location
 * @property int|null $visibility_id
 * @property string|null $facebook_username
 * @property string|null $twitter_username
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $default_theme
 * @property-read mixed $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Link[] $links
 * @property-read int|null $links_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Photo[] $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Profile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Profile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Profile query()
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereDefaultTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereFacebookUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereTwitterUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Profile whereVisibilityId($value)
 * @mixin \Eloquent
 */
class Profile extends Eloquent
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();
    }

    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'first_name', 'last_name', 'bio', 'alias', 'location', 'facebook_username', 'twitter_username', 'default_theme', 'setting_weekly_update', 'setting_daily_update', 'setting_instant_update'
    ];

    protected $dates = ['updated_at'];

    /**
     * An profile is owned by a user
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function getFullNameAttribute()
    {
        $user = $this->user();

        return $user->first_name . ' ' . $user->last_name;
    }

    /**
     * An profile is owned by a user
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The links that belong to the entity
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function links()
    {
        return $this->belongsToMany(Link::class);
    }

    /**
     * Get all of the entities photos
     */
    public function photos()
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }
}
