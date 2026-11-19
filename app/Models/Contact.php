<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    protected static function booted(): void
    {
        // hidden along with an unlisted entity it belongs to
        static::addGlobalScope('listedEntity', \App\Models\Scopes\UnlistedEntityScope::following('id', 'contact_entity.contact_id'));
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'contacts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'email', 'phone', 'other', 'type', 'visibility_id'];

    /**
     * Get the entities that belong to the contact.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Entity')->withTimestamps();
    }
}
