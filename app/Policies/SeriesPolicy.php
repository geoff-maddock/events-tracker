<?php

namespace App\Policies;

use App\Models\Series;
use App\Models\User;

class SeriesPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider

    public function delete(User $user, Series $series): bool
    {
        return $series->ownedBy($user) || $user->hasGroup('super_admin');
    }
}
