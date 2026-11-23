<?php

namespace App\Services;

use App\Mail\UserUpdate;
use App\Mail\WeeklyEssentials;
use App\Mail\WeeklyUpdate;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;

/**
 * Sends one user their daily or weekly digest now, on request ("Send Reminder"
 * and "Send Weekly Update" on a profile). The scheduled `notify` and
 * `notifyWeekly` commands send the same digests to everyone.
 */
class DigestSender
{
    public function __construct(private readonly DigestBuilder $digests)
    {
    }

    /**
     * The daily digest, sent even when it lists nothing.
     */
    public function daily(User $user, User $by): DigestSendResult
    {
        if ($user->profile?->setting_daily_update !== 1) {
            return DigestSendResult::Disabled;
        }

        $digest = $this->digests->daily($user);
        $mail = new UserUpdate(url('/').'/', config('app.app_name'), config('app.admin'), config('app.noreplyemail'), $user, $digest->attending, $digest->series, $digest->interests);

        return $this->send($user, $by, $mail, 'User '.$user->name.' was sent a reminder');
    }

    /**
     * The same digest the scheduled notifyWeekly command sends, including its
     * Essential Events fallback when there is nothing personal (#2102).
     */
    public function weekly(User $user, User $by): DigestSendResult
    {
        if ($user->profile?->setting_weekly_update !== 1) {
            return DigestSendResult::Disabled;
        }

        $digest = $this->digests->weekly($user);
        $essentials = $digest->isEmpty() ? $this->digests->essentials($user) : null;
        if ($essentials?->isEmpty()) {
            return DigestSendResult::NothingToSend;
        }

        $mail = $essentials
            ? new WeeklyEssentials(url('/').'/', config('app.app_name'), config('app.admin'), config('app.noreplyemail'), $user, $essentials)
            : new WeeklyUpdate(url('/').'/', config('app.app_name'), config('app.admin'), config('app.noreplyemail'), $user, $digest->attending, $digest->series, $digest->interests);

        return $this->send($user, $by, $mail, 'User '.$user->name.' was sent a weekly reminder');
    }

    private function send(User $user, User $by, Mailable $mail, string $logLine): DigestSendResult
    {
        if (!(new BestEffortMailer())->send($user->email, $mail, ['user_id' => $user->id])) {
            return DigestSendResult::Failed;
        }

        Activity::log($user, $by, Action::REMINDER);
        Log::info($logLine);

        return DigestSendResult::Sent;
    }
}
