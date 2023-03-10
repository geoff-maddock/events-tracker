<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property \App\Models\Visibility|null $visibility
 **/
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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'menu_parent_id' => null,
        'body' => null,
    ];

    /**
     * A menu has at most one menu parent.
     */
    public function menuParent(): HasOne
    {
        return $this->hasOne(Menu::class, 'id', 'menu_parent_id')->withDefault(['menu_parent_id' => null]);
    }

    /**
     * A menu has one visibility.
     */
    public function visibility(): HasOne
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * Returns visible menus.
     */
    public function scopeVisible(Builder $query): Builder
    {
        $public = Visibility::where('name', '=', 'Public')->first();

        return $query->where('visibility_id', '=', $public ? $public->id : null);
    }

    /**
     * The blogs that belong to the menu.
     */
    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    /**
     * Set the menu_parent attribute.
     */
    public function setMenuParent(string $value): void
    {
        if (!empty($value)) {
            $this->attributes['menu_parent'] = $value;
        } else {
            $this->attributes['menu_parent'] = null;
        }
    }
}
