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
 * Admins keep their verification: an admin changing someone else's email made
 * the change themselves, as when SignupReviewController vouches for an
 * account, and an admin changing their own must not be locked out of the
 * site when mail is down.
 *
 * Usage: fill the user, call prepare() before save() and, when it returns an
 * old address, notify() after the rest of the update has been saved.
 */
class EmailChangeVerifier
{
    /**
     * Self-made email changes allowed per user per hour. Each sends two
     * emails, so without a cap the profile form could mail strangers.
     */
    public const MAX_CHANGES_PER_HOUR = 3;

    /**
     * Whether the address prepare() replaced was verified, so notify() only
     * writes to an inbox the account had confirmed.
     */
    private bool $oldEmailWasVerified = false;

    /**
     * Clear the verification of a self-made email change. Returns the old
     * address when the change needs a new verification, otherwise null.
     *
     * @throws ValidationException when the user changes their email too often
     */
    public function prepare(User $user, User $actor): ?string
    {
        if (!$user->exists || $actor->id !== $user->id || $actor->can('grant_access') || !$user->isDirty('email')) {
            return null;
        }

        $oldEmail = (string) $user->getOriginal('email');

        // a change of case alone is the same inbox
        if (0 === strcasecmp($oldEmail, (string) $user->email)) {
            return null;
        }

        $key = 'email-change:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, self::MAX_CHANGES_PER_HOUR)) {
            throw ValidationException::withMessages(['email' => 'You have changed your email too many times. Please try again later.']);
        }
        RateLimiter::hit($key, 3600);

        $this->oldEmailWasVerified = $user->hasVerifiedEmail();

        // marks an account unverified only by this change, which the
        // never-verified signup cleanup and purge leave alone
        if ($this->oldEmailWasVerified) {
            $user->email_change_pending_at = now();
        }
        $user->email_verified_at = null;

        return $oldEmail;
    }

    /**
     * Send the verification link to the new address and, when the old address
     * was verified, tell it the email was changed, which helps spot an account
     * takeover. The change is already saved, so a mail failure is logged
     * rather than failing the request; the user can resend the link.
     */
    public function notify(User $user, string $oldEmail): void
    {
        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);
            Log::warning('EmailChangeVerifier: verification email failed to send', ['user_id' => $user->id]);
        }

        // an address that was never confirmed may belong to someone else
        if (!$this->oldEmailWasVerified) {
            return;
        }

        (new BestEffortMailer())->send($oldEmail, new EmailChanged(
            (string) config('app.url'),
            (string) config('app.app_name'),
            (string) config('app.admin'),
            (string) config('app.noreplyemail'),
            $user
        ), ['user_id' => $user->id]);
    }
}
