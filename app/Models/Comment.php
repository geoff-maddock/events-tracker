<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

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

    protected static function booted(): void
    {
        // attribute the comment to its author; without this created_by kept the
        // DB default of 1 and only user 1 could edit or delete a comment
        static::creating(function (Comment $comment) {
            if (empty($comment->created_by) && Auth::id()) {
                $comment->created_by = Auth::id();
            }
        });
    }

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
