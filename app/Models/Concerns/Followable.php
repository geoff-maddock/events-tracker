<?php

namespace App\Models\Concerns;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A model users can follow.
 *
 * Follows are rows of the `follows` table with the model's morph alias
 * ('entity', 'series', 'tag', 'thread') in `object_type`; the aliases are
 * registered in AppServiceProvider.
 */
trait Followable
{
    /**
     * @return MorphMany<Follow, $this>
     */
    public function follows(): MorphMany
    {
        return $this->morphMany(Follow::class, 'object', 'object_type', 'object_id');
    }

    /**
     * The user's follow of this model, if there is one.
     */
    public function followedBy(?User $user): ?Follow
    {
        if (!$user) {
            return null;
        }

        return Follow::where('object_type', '=', $this->getMorphClass())
            ->where('object_id', '=', $this->getKey())
            ->where('user_id', '=', $user->id)
            ->first();
    }

    /**
     * The users who follow this model, with the profile that holds each
     * one's notification settings.
     *
     * @return Collection<int, User>
     */
    public function followers(): Collection
    {
        return User::with('profile')->join('follows', 'users.id', '=', 'follows.user_id')
            ->where('follows.object_type', $this->getMorphClass())
            ->where('follows.object_id', $this->getKey())
            ->get('users.*');
    }
}
