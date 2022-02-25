<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Model $commentable
 */
class Comment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'comments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['message', 'commentable_id', 'commentable_type'];

    /**
     * Get all of the owning commentable models.
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the author of the comment.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * Returns entities created by the user.
     */
    public function scopeCreatedBy(Builder $query, User $user): Builder
    {
        return $query->where('created_by', '=', $user->id);
    }
}
