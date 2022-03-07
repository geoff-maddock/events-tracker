<?php

namespace App\Listeners;

use App\Mail\LoginFailure;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Mail;

class LogFailedLogin
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
    public function handle(Failed $event)
    {
        // add activity log of failed login
        Activity::logFailure($event);

        $maxFailures = 5;

        // check logs in the past 30 mins for failed logs for this user
        $fails = Activity::where('created_at', '>', Carbon::now()->subMinutes(30))
            ->where('action_id', '=', 14)
            ->where('object_name', '=', $event->credentials['email'])
            ->count();

        if ($fails > $maxFailures && $event->user) {
            // send an email to the user
            $reply_email = config('app.noreplyemail');
            $admin_email = config('app.admin');
            $site = config('app.app_name');
            $url = config('app.url');
            $email = $event->credentials['email'];
            $user = $event->user;

            Mail::to($email)->send(new LoginFailure($url, $site, $admin_email, $reply_email, $user, $fails));
        }
    }
}
