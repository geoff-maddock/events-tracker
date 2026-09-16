<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\User;

class LinkPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider

    public function delete(User $user, Link $link): bool
    {
        // links have no creator, so ownership comes from the entities they are attached to
        return $user->hasGroup('super_admin')
            || $link->entities()->where('entities.created_by', $user->id)->exists();
    }
}
