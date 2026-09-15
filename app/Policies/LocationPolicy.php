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
        if ($user->hasGroup('super_admin') || (int) $location->created_by === $user->id) {
            return true;
        }

        // the owner of the entity the location belongs to
        return Entity::whereKey($location->entity_id)->where('created_by', $user->id)->exists();
    }
}
