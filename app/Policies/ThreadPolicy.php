<?php

namespace App\Policies;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class ThreadPolicy
{
    use HandlesAuthorization;

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
        // the author or a super_admin; the `admin` group passes via Gate::before
        return (int) $thread->created_by === $user->id || $user->hasGroup('super_admin');
    }

    /**
     * Determine whether the user can delete the thread.
     */
    public function delete(User $user, Thread $thread): bool
    {
        return $this->update($user, $thread);
    }

    /**
     * Determine whether the user can view the thread.
     */
    public function all(Thread $thread): bool
    {
        return true;
    }
}
