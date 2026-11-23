<?php

namespace App\Services;

use App\Mail\UserActivation;
use App\Mail\UserSuspended;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Support\Facades\Log;

/**
 * The changes an admin makes to a user's account: activate, suspend, delete,
 * purge, and the signup review's approve and ban. Controllers keep the
 * authorization and the flash messages.
 */
class UserAccounts
{
    /**
     * Active, verified and trusted. Returns false when the account was activated
     * but the courtesy email could not be sent.
     */
    public function activate(User $user, User $by): bool
    {
        $user->user_status_id = UserStatus::ACTIVE;
        $user->email_verified_at = now();
        $user->email_change_pending_at = null;
        // an admin vouching for the account also trusts it (#2312)
        $user->trusted_at = now();
        $user->save();

        Log::info('User '.$user->name.' is activated.');
        Activity::log($user, $by, Action::ACTIVATE);

        return (new BestEffortMailer())->send(
            $user->email,
            new UserActivation(config('app.url'), config('app.app_name'), config('app.admin'), config('app.noreplyemail'), $user),
            ['user_id' => $user->id]
        );
    }

    /**
     * Suspended. Returns false when the account was suspended but the courtesy
     * email could not be sent.
     */
    public function suspend(User $user, User $by): bool
    {
        $user->user_status_id = UserStatus::SUSPENDED;
        $user->save();

        Activity::log($user, $by, Action::SUSPEND);
        Log::info('User '.$user->name.' is suspended.');

        return (new BestEffortMailer())->send(
            $user->email,
            new UserSuspended(config('app.url'), config('app.app_name'), config('app.admin'), config('app.noreplyemail'), $user),
            ['user_id' => $user->id]
        );
    }

    /**
     * A hard delete; the model passes the user's events to the site admin.
     */
    public function delete(User $user, User $by): void
    {
        Activity::log($user, $by, Action::DELETE);
        Log::info('User '.$user->name.' is deleted.');

        $user->delete();
    }

    /**
     * Deletes the pending users who never verified their email. A verified signup
     * that the risk score holds at Pending waits in the signup review instead
     * (#2312), even after changing its email (#2321).
     *
     * @return list<string> the names of the deleted users
     */
    public function purgeUnverified(): array
    {
        $users = User::where('user_status_id', '=', UserStatus::PENDING)
            ->whereNull('email_verified_at')
            ->whereNull('email_change_pending_at')
            ->orderBy('name', 'ASC')
            ->get();

        if ($users->isEmpty()) {
            return [];
        }

        Log::info('Purging users.');
        $names = [];
        foreach ($users as $user) {
            $names[] = $user->name;
            $user->delete();
        }

        return $names;
    }

    /**
     * Signup review: trusted from now on, active, verified, and its profile
     * shown to everyone.
     */
    public function approveSignup(User $user, User $by): void
    {
        $user->trusted_at = now();
        $user->user_status_id = UserStatus::ACTIVE;
        $user->email_verified_at ??= now()->toDateTimeString();
        $user->email_change_pending_at = null;
        $user->save();

        Activity::log($user, $by, Action::ACTIVATE, sprintf('Approved signup: %s', $user->name));
    }

    /**
     * Signup review: banned, with the spam cleared from its profile. The row
     * stays so the email address can't simply register again; no ban email is
     * sent to a spam account.
     */
    public function banAsSpam(User $user, User $by): void
    {
        $user->user_status_id = UserStatus::BANNED;
        $user->trusted_at = null;
        $user->save();

        $user->profile?->fill([
            'bio' => null,
            'facebook_username' => null,
            'twitter_username' => null,
            'instagram_username' => null,
        ])->save();

        Activity::log($user, $by, Action::SUSPEND, sprintf('Banned as spam: %s', $user->name));
    }
}
