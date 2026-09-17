<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\User;

class EntityPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider
    // created_by is attribution only; control comes from entity_owners (#2147)

    /**
     * Edit the entity and manage its links, locations, contacts and photos.
     */
    public function update(User $user, Entity $entity): bool
    {
        return $entity->isOwnedBy($user) || $user->hasGroup('super_admin');
    }

    public function delete(User $user, Entity $entity): bool
    {
        return $entity->isOwnedBy($user) || $user->hasGroup('super_admin');
    }
}
