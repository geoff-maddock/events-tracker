<?php

namespace App\Listeners;

use App\Activity;
use App\User;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Auth;

class RouterMatchedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(RouteMatched $route)
    {
        // get the user
        $user = Auth::user();

        // log matches where relevant
        if (isset($route->route->action['as']) && 'user.impersonate' === $route->route->action['as']) {
            // get the user who was impersonated
            $who = User::find($route->route->parameter('user'));

            // log impersonation
            Activity::log($user, $user, 13, $user->name.' impersonated '.$who->name);
        }
    }
}
