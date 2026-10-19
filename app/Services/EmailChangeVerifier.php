<?php

namespace App\Services;

use App\Mail\EmailChanged;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * A user who changes their own email must verify the new address before
 * content writes resume (#2321). Otherwise `verified` only proves the account
 * once confirmed some inbox, not the one it uses now.
 *
 * Admins don't take verification away: an admin changing someone else's
 * email made the change themselves, as when SignupReviewController vouches
 * for an account, and an admin changing their own must not be locked out of
 * the site when mail is down. An admin correcting the email of an account
 * that isn't verified sends a new link to the corrected address.
 *
 * Usage: fill the user, call prepare() before save() and, when it returns a
 * change, notify() after the rest of the update has been saved.
 */
class EmailChangeVerifier
{
    /**
     * Self-made email changes allowed per user per hour. Each sends mail, so
     * without a cap the profile form could mail strangers.
     */
    public const MAX_CHANGES_PER_HOUR = 3;

    /**
     * Self-made email changes allowed per IP per hour, across accounts, so
     * a batch of throwaway signups can't multiply the per-user cap.
     */
    public const MAX_CHANGES_PER_IP_PER_HOUR = 10;

    /**
     * Clear the verification of a self-made email change. Returns the change
     * when it needs a new verification link, otherwise null.
     *
     * @throws ValidationException when the user or IP changes email too often
     */
    public function prepare(User $user, User $actor, ?string $ip): ?EmailChange
    {
        if (!$user->exists || !$user->isDirty('email')) {
            return null;
        }

        $oldEmail = (string) $user->getOriginal('email');

        // a change of case alone is the same inbox
        if (0 === strcasecmp($oldEmail, (string) $user->email)) {
            return null;
        }

        if ($actor->id !== $user->id || $actor->can('grant_access')) {
            return $user->hasVerifiedEmail() ? null : new EmailChange($oldEmail, false);
        }

        $limits = ['email-change:'.$user->id => self::MAX_CHANGES_PER_HOUR];
        if (null !== $ip) {
            $limits['email-change-ip:'.$ip] = self::MAX_CHANGES_PER_IP_PER_HOUR;
        }
        foreach ($limits as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw ValidationException::withMessages(['email' => 'You have changed your email too many times. Please try again later.']);
            }
        }

        $oldEmailWasVerified = $user->hasVerifiedEmail();

        // marks an account unverified only by this change, which the
        // never-verified signup cleanup and purge leave alone
        if ($oldEmailWasVerified) {
            $user->email_change_pending_at = now();
        }
        $user->email_verified_at = null;

        return new EmailChange($oldEmail, $oldEmailWasVerified, array_keys($limits));
    }

    /**
     * Send the verification link to the new address and, when the old address
     * was verified, tell it the email was changed, which helps spot an account
     * takeover. The change is already saved, so a mail failure is logged
     * rather than failing the request; the user can resend the link.
     */
    public function notify(User $user, EmailChange $change): void
    {
        // counted only once the change is saved
        foreach ($change->limiterKeys as $key) {
            RateLimiter::hit($key, 3600);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);
            Log::warning('EmailChangeVerifier: verification email failed to send', ['user_id' => $user->id]);
        }

        // an address that was never confirmed may belong to someone else
        if (!$change->oldEmailWasVerified) {
            return;
        }

        (new BestEffortMailer())->send($change->oldEmail, new EmailChanged(
            (string) config('app.url'),
            (string) config('app.app_name'),
            (string) config('app.admin'),
            (string) config('app.noreplyemail'),
            $user
        ), ['user_id' => $user->id]);
    }
}
