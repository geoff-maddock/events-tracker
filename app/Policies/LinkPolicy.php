<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\User;

class LinkPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider

    public function update(User $user, Link $link): bool
    {
        return $this->delete($user, $link);
    }

    public function delete(User $user, Link $link): bool
    {
        // links have no creator, so ownership comes from the entities they are attached to
        return $user->hasGroup('super_admin')
            || $link->entities()->whereHas('owners', fn ($q) => $q->where('users.id', $user->id))->exists();
    }
}
