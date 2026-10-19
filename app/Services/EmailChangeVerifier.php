<?php

namespace App\Services;

use App\Mail\EmailChanged;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A user who changes their own email must verify the new address before
 * content writes resume (#2321). Otherwise `verified` only proves the account
 * once confirmed some inbox, not the one it uses now.
 *
 * An admin changing someone else's email keeps them verified: the admin made
 * the change, as when SignupReviewController vouches for an account.
 *
 * Usage: fill the user, call prepare() before save() and, when it returns an
 * old address, notify() after save().
 */
class EmailChangeVerifier
{
    /**
     * Clear the verification of a self-made email change. Returns the old
     * address when the change needs a new verification, otherwise null.
     */
    public function prepare(User $user, User $actor): ?string
    {
        if (!$user->exists || $actor->id !== $user->id || !$user->isDirty('email')) {
            return null;
        }

        $oldEmail = (string) $user->getOriginal('email');

        // a change of case alone is the same inbox
        if (0 === strcasecmp($oldEmail, (string) $user->email)) {
            return null;
        }

        $user->email_verified_at = null;

        return $oldEmail;
    }

    /**
     * Send the verification link to the new address and tell the old one the
     * email was changed, which helps spot an account takeover. The change is
     * already saved, so a mail failure is logged rather than failing the
     * request; the user can resend the link.
     */
    public function notify(User $user, string $oldEmail): void
    {
        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);
            Log::warning('EmailChangeVerifier: verification email failed to send', ['user_id' => $user->id]);
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
