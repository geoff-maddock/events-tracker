<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['text', 'url', 'title', 'is_primary'];

    /**
     * Get the entities that belong to the link
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get a full link tag
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getTagAttribute()
    {
        return sprintf('<a href="%s" title="%s">%s</a>', $this->url, $this->title, $this->text);
    }
}
