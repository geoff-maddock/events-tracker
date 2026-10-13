<?php

namespace App\Listeners;

use App\Events\UserBanned;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\Signup\SignupRiskScorer;
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
        $user = User::where('email', $event->user->getEmailForVerification())->first();

        // only a pending account activates; a banned or suspended one stays so
        if ($user === null || ($user->user_status_id !== null && (int) $user->user_status_id !== UserStatus::PENDING)) {
            return;
        }

        // a signup that scored as risky stays pending for admin review (#2312)
        if (app(SignupRiskScorer::class)->isRisky($user)) {
            return;
        }

        $user->user_status_id = UserStatus::ACTIVE;
        $user->save();
    }
}
