<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Profile.
 *
 * @property int                                                         $id
 * @property int                                                         $user_id
 * @property string|null                                                 $bio
 * @property string|null                                                 $alias
 * @property string|null                                                 $location
 * @property int|null                                                    $visibility_id
 * @property string|null                                                 $facebook_username
 * @property string|null                                                 $twitter_username
 * @property \Illuminate\Support\Carbon                                  $created_at
 * @property \Illuminate\Support\Carbon                                  $updated_at
 * @property string|null                                                 $first_name
 * @property string|null                                                 $last_name
 * @property string|null                                                 $default_theme
 * @property int|null                                                    $setting_weekly_update
 * @property int|null                                                    $setting_daily_update
 * @property int|null                                                    $setting_instant_update
 * @property mixed                                                       $full_name
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Link[] $links
 * @property int|null                                                    $links_count
 * @property \Illuminate\Database\Eloquent\Collection|Photo[]            $photos
 * @property int|null                                                    $photos_count
 * @property User                                                        $user
 *
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
     * @var array
     *
     **/
    protected $fillable = [
        'first_name', 'last_name', 'bio', 'alias', 'location', 'facebook_username', 'twitter_username', 'default_theme', 'setting_weekly_update', 'setting_daily_update', 'setting_instant_update',
    ];

    protected $dates = ['updated_at'];

    /**
     * An profile is owned by a user.
     */
    public function getFullNameAttribute(): string
    {
        $user = $this->user();

        return $user->first_name.' '.$user->last_name;
    }

    /**
     * An profile is owned by a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The links that belong to the entity.
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Link::class);
    }

    /**
     * Get all of the entities photos.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }
}
