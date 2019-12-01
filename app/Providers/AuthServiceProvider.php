<?php

namespace App\Providers;

use App\Permission;
use App\Policies\PostPolicy;
use App\Policies\ThreadPolicy;
use App\Post;
use App\Thread;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

//use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
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
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

        // super admins can do anything
        $gate->before(function ($user, $ability) {
            if ($user->hasGroup('super_admin')) {
                return true;
            }
        });

        // adds a gate for each permission name - checks whether the user has a group that matches one of the permission groups
        foreach ($this->getPermissions() as $permission) {
            $gate->define($permission->name, function ($user) use ($permission) {
                return $user->hasGroup($permission->groups);
            });
        }

        Gate::before(function ($user) {
            if ($user->hasGroup('admin')) {
                return true;
            }
        });
    }

    protected function getPermissions()
    {
        return Permission::with('groups')->get();
    }
}
