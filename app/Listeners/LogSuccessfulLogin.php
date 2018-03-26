<?php

namespace App\Listeners;

use App\Activity;
use App\Events\UserBanned;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle()
    {
        // get the user
        $user = Auth::user();

        // add login to log
        Activity::log($user, $user, 4);
    }
}
