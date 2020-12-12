<?php

namespace App\Policies;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     */
    public function __construct()
    {
    }

    public function update(User $user, Forum $forum)
    {
        return $user->owns($forum);
    }

    public function show(User $user, Forum $forum)
    {
        return true;
    }
}
