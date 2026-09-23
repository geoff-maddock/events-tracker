<?php

namespace App\Policies;

use App\Models\Photo;
use App\Models\User;

class PhotoPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider

    // set/unset primary and event flags; same people who may delete it
    public function update(User $user, Photo $photo): bool
    {
        return $this->delete($user, $photo);
    }

    public function delete(User $user, Photo $photo): bool
    {
        if ($user->hasGroup('super_admin') || (int) $photo->created_by === $user->id) {
            return true;
        }

        // the owner of the gallery a photo is attached to may manage it,
        // matching the delete button in partials/photo-gallery-tw
        return $photo->events()->where('events.created_by', $user->id)->exists()
            || $photo->entities()->whereHas('owners', fn ($q) => $q->where('users.id', $user->id))->exists()
            || $photo->series()->where('series.created_by', $user->id)->exists()
            || $photo->users()->whereKey($user->id)->exists();
    }
}
