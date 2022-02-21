<?php

namespace App\Policies;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class ThreadPolicy
{
    use HandlesAuthorization;

    public function before(User $user): bool
    {
        // instant authorizes the selected user
        if ($user->id === 1) {
            return true;
        }

        return true;
    }

    /**
     * Determine whether the user can view the thread.
     */
    public function view(User $user, Thread $thread): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create threads.
     */
    public function create(User $user): bool
    {
        if (Auth::check()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the thread.
     */
    public function update(User $user, Thread $thread): bool
    {
        return $thread->user->id == $user->id;
    }

    /**
     * Determine whether the user can delete the thread.
     */
    public function delete(User $user, Thread $thread): bool
    {
        if (Auth::check()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the thread.
     */
    public function all(Thread $thread): bool
    {
        return true;
    }
}
