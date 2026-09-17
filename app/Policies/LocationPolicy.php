<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider

    public function delete(User $user, Location $location): bool
    {
        if ($user->hasGroup('super_admin')) {
            return true;
        }

        // only the entity's current owners: whoever added the location loses
        // control of it once the entity is transferred
        return Entity::whereKey($location->entity_id)->ownedBy($user)->exists();
    }
}
