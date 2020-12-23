<?php

namespace App\Models;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

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
        'first_name', 'last_name', 'bio', 'alias', 'location', 'facebook_username', 'twitter_username', 'default_theme'
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
