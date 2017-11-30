<?php

namespace App\Providers;

use App\Permission;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
//use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        Post::class => PostPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

//        Gate::resource('posts', 'PostPolicy');
//
//        Gate::define('show_forum', function ($user, $post) {
//            return 1;
//        });

        // super admins can do anything
        $gate->before(function ($user, $ability) {
            if ($user->hasGroup('super_admin')) {
                return true;
            }
        });

        // gets permissions
        foreach ($this->getPermissions() as $permission) {
            $gate->define($permission->name, function($user) use ($permission) {
                return $user->hasGroup($permission->groups);
            });
        }
    }

    protected function getPermissions()
    {
        return Permission::with('groups')->get();
    }


}