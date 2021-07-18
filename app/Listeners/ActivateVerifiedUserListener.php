<?php

namespace App\Listeners;

use App\Events\UserBanned;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Events\Verified;

class ActivateVerifiedUserListener
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
     * @param  Verified  $event
     * @return void
     */
    public function handle(Verified $event)
    {
        // change the user status to activated
        $user = User::where('email', $event->user->getEmailForVerification())->first();
        $user->user_status_id = UserStatus::ACTIVE;
        $user->save();
    }
}
