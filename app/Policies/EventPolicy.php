<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class EventPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     */
    public function __construct()
    {
    }

    /**
     * Determine whether the user can create threads.
     *
     * @param  User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if (Auth::check() && $user->getIsActiveAttribute()) {
            return true;
        }

        return false;
    }

    // the `admin` group is granted everything by Gate::before in AuthServiceProvider
    public function delete(User $user, Event $event): bool
    {
        return $event->ownedBy($user) || $user->hasGroup('super_admin');
    }
}
