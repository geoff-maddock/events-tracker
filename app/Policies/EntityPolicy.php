<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\User;

class EntityPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider

    public function delete(User $user, Entity $entity): bool
    {
        return (int) $entity->created_by === $user->id || $user->hasGroup('super_admin');
    }
}
