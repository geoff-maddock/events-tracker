<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Menu extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'body',
        'menu_parent_id',
        'visibility_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $attributes = [
        'menu_parent_id' => null,
        'body' => null
    ];

    /**
     * A menu has at most one menu parent.
     */
    public function menuParent()
    {
        return $this->hasOne(Menu::class, 'id', 'menu_parent_id')->withDefault(['menu_parent_id' => null]);
    }

    /**
     * A location has one visibility.
     */
    public function visibility()
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * Returns visible events.
     */
    public function scopeVisible($query, $user)
    {
        $public = Visibility::where('name', '=', 'Public')->first();

        $query->where('visibility_id', '=', $public ? $public->id : null);
    }

    /**
     * The blogs that belong to the menu.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    /**
     * Set the soundcheck_at attribute.
     */
    public function setMenuParent($value)
    {
        if (!empty($value)) {
            $this->attributes['menu_parent'] = $value;
        } else {
            $this->attributes['menu_parent'] = null;
        }
    }
}
