<?php

namespace App\Policies;

use App\Models\Series;
use App\Models\User;

class SeriesPolicy
{
    // the `admin` group is granted everything by Gate::before in AuthServiceProvider

    /**
     * Edit the series and add to it (photos, a discussion thread, social posts):
     * its owner, or an admin. The same rule on the web and through the API (#2395).
     */
    public function update(User $user, Series $series): bool
    {
        return $series->ownedBy($user) || $user->hasGroup('super_admin');
    }

    public function delete(User $user, Series $series): bool
    {
        return $series->ownedBy($user) || $user->hasGroup('super_admin');
    }
}
