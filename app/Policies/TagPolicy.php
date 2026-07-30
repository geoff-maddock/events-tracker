<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider

    public function update(User $user, Tag $tag): bool
    {
        return $user->hasGroup('super_admin')
            || ($tag->created_by && $user->id === $tag->created_by);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasGroup('super_admin');
    }
}
