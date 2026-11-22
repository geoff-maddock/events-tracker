<?php

namespace App\Models\Concerns;

use App\Models\Like;
use App\Models\User;

/**
 * Likes for a model that also stores their number in a `likes` column.
 *
 * One like per user, and the column moves only when a like row is added or
 * removed, so a repeated request changes nothing. (The column shares its name
 * with the likes() relation, so it is changed with increment()/decrement(),
 * never through `$model->likes`.)
 */
trait HasLikeCounter
{
    /** The `object_type` this model's likes are stored under. */
    abstract protected function likeType(): string;

    public function addLike(User $user): void
    {
        $like = Like::firstOrCreate([
            'object_type' => $this->likeType(),
            'object_id' => $this->getKey(),
            'user_id' => $user->id,
        ]);

        // only a new like counts, so repeating the request changes nothing
        if ($like->wasRecentlyCreated) {
            $this->increment('likes');
        }
    }

    public function removeLike(User $user): void
    {
        $removed = Like::where('object_type', $this->likeType())
            ->where('object_id', $this->getKey())
            ->where('user_id', $user->id)
            ->delete();

        if ($removed > 0) {
            $this->decrement('likes');
        }
    }
}
