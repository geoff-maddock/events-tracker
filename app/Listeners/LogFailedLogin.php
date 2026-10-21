<?php

namespace App\Listeners;

use App\Mail\LoginFailure;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class LogFailedLogin
{
    // failed attempts are counted, and the warning mail sent at most once, per this window
    private const WINDOW_MINUTES = 30;

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

        // one warning per account per window: every failure past the limit used to
        // send its own mail (and admin bcc), so repeated attempts flooded both inboxes
        if ($fails > $maxFailures && $event->user
            && Cache::add('login-failure-mail:'.$event->user->getAuthIdentifier(), true, now()->addMinutes(self::WINDOW_MINUTES))) {
            // send an email to the user
            $reply_email = config('app.noreplyemail');
            $admin_email = config('app.admin');
            $site = config('app.app_name');
            $url = config('app.url');
            $email = $event->credentials['email'];
            $user = $event->user;

            // a mail failure must not turn a failed login into a 500
            try {
                Mail::to($email)->send(new LoginFailure($url, $site, $admin_email, $reply_email, $user, $fails));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
