<?php

namespace App\Policies;

use App\User;
use App\Thread;
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
     * @param  \App\User  $user
     * @param  \App\Thread  $thread
     * @return mixed
     */
    public function view(User $user, Thread $thread)
    {
        return true;
    }

    /**
     * Determine whether the user can create threads.
     *
     * @param  \App\User  $user
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
     * @param  \App\User  $user
     * @param  \App\Thread  $thread
     * @return mixed
     */
    public function update(User $user, Thread $thread)
    {
        return $thread->user_id == $user->id;
    }

    /**
     * Determine whether the user can delete the thread.
     *
     * @param  \App\User  $user
     * @param  \App\Thread  $thread
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
     * @param  \App\Thread  $thread
     * @return mixed
     */
    public function all(Thread $thread)
    {
        return true;
    }
}
