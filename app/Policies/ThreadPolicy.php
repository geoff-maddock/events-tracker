<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Thread;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class ThreadPolicy
{
    use HandlesAuthorization;

    public function before($user)
    {
        // instant authorizes the selected user
        if ($user->id === 1) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the thread.
     *
     * @param  User  $user
     * @param  Thread  $thread
     * @return mixed
     */
    public function view(User $user, Thread $thread)
    {
        return true;
    }

    /**
     * Determine whether the user can create threads.
     *
     * @param  User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if (Auth::check()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the thread.
     *
     * @param  User  $user
     * @param  Thread  $thread
     * @return mixed
     */
    public function update(User $user, Thread $thread)
    {
        return $thread->user->id == $user->id;
    }

    /**
     * Determine whether the user can delete the thread.
     *
     * @param  User  $user
     * @param  Thread  $thread
     * @return mixed
     */
    public function delete(User $user, Thread $thread)
    {
        if (Auth::check()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the thread.
     *
     * @param  Thread $thread
     * @return mixed
     */
    public function all(Thread $thread)
    {
        return true;
    }
}
