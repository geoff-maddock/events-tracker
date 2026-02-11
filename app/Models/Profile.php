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
 * @property string|null                                                 $instagram_username
 * @property \Illuminate\Support\Carbon                                  $created_at
 * @property \Illuminate\Support\Carbon                                  $updated_at
 * @property string|null                                                 $first_name
 * @property string|null                                                 $last_name
 * @property string|null                                                 $default_theme
 * @property int|null                                                    $setting_weekly_update
 * @property int|null                                                    $setting_daily_update
 * @property int|null                                                    $setting_instant_update
 * @property int|null                                                    $setting_forum_update
 * @property int|null                                                    $setting_public_profile
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

    protected $fillable = [
        'first_name', 'last_name', 'bio', 'alias', 'location', 'facebook_username', 'twitter_username', 'instagram_username','default_theme', 'setting_weekly_update', 'setting_daily_update', 'setting_instant_update', 'setting_forum_update', 'setting_public_profile'
    ];

    protected $attributes = [
        'default_theme' => 'dark',
        'setting_public_profile' => 1,
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * An profile is owned by a user.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * An profile is owned by a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
