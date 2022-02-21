<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Post $post): bool
    {
        return $post->created_by == $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $post->created_by == $user->id;
    }

    public function destroy(User $user, Post $post): bool
    {
        return $post->created_by == $user->id && $post->isRecent();
    }
}
