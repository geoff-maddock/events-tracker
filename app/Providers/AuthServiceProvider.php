<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Post;
use App\Models\Thread;
use App\Policies\PostPolicy;
use App\Policies\ThreadPolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Schema;

//use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Post::class => PostPolicy::class,
        Thread::class => ThreadPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::before(function ($user) {
            if ($user->hasGroup('admin')) {
                return true;
            }
        });

        // adds a gate for each permission name - checks whether the user has a group that matches one of the permission groups
        // disabling because this is causing an issue with php artisan when the database isn't initialized
        foreach ($this->getPermissions() as $permission) {
            Gate::define($permission->name, function ($user) use ($permission) {
                return $user->hasGroup($permission->groups);
            });
        }

        Gate::before(function ($user) {
            if ($user->hasGroup('admin')) {
                return true;
            }
        });
    }

    protected function getPermissions(): Collection
    {
        // doing this check to make sure the table exists
        // since it's in a provider, it might be called by php artisan before the db is migrated

        if (!Schema::hasTable('permissions')) {
            return new Collection([]);
        }

        return Permission::with('groups')->get();
    }
}
