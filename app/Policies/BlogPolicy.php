<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlogPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Blog $blog): bool
    {
        return $blog->created_by == $user->id;
    }

    public function delete(User $user, Blog $blog): bool
    {
        return $blog->created_by == $user->id;
    }

    public function destroy(User $user, Blog $blog): bool
    {
        return $blog->created_by == $user->id;
    }
}
