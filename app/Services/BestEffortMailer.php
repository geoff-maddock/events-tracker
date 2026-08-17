<?php

namespace App\Services;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends notification email without letting a mail failure break the request
 * that triggered it.
 *
 * Follower notifications are best-effort: by the time they are sent the user's
 * action (creating an event, posting to a thread) has already been committed,
 * so an unreachable SMTP server should be logged and reported — not turned
 * into a 500 that tells the user their work failed when it did not.
 *
 * A total outage would otherwise cost one SMTP connection attempt per
 * follower, turning a broken mail server into a hung request. After
 * MAX_CONSECUTIVE_FAILURES the sender trips and skips the rest of the batch,
 * so an isolated bad address is still tolerated but a dead transport gives up
 * quickly.
 *
 * One instance is intended per batch — construct it, send through it, then
 * read the counts for logging.
 */
class BestEffortMailer
{
    /**
     * Consecutive failures that trip the breaker for the rest of the batch.
     */
    public const MAX_CONSECUTIVE_FAILURES = 3;

    private int $consecutiveFailures = 0;

    private int $sent = 0;

    private int $failed = 0;

    private int $skipped = 0;

    private bool $tripped = false;

    /**
     * Attempt to send one Mailable. Returns true only if it was handed to the
     * transport without error.
     *
     * @param array<string, mixed> $context extra fields for the failure log
     */
    public function send(string $email, Mailable $mailable, array $context = []): bool
    {
        return $this->attempt(
            fn () => Mail::to($email)->send($mailable),
            $context + ['mailable' => $mailable::class]
        );
    }

    /**
     * Attempt an arbitrary send. Use for call sites that still build mail with
     * the Mail::send($view, $data, $closure) form rather than a Mailable.
     *
     * @param callable():mixed     $send
     * @param array<string, mixed> $context extra fields for the failure log
     */
    public function attempt(callable $send, array $context = []): bool
    {
        if ($this->tripped) {
            ++$this->skipped;

            return false;
        }

        try {
            $send();
            $this->consecutiveFailures = 0;
            ++$this->sent;

            return true;
        } catch (Throwable $e) {
            ++$this->failed;
            ++$this->consecutiveFailures;

            Log::warning('BestEffortMailer: notification email failed to send', $context + [
                'error' => $e->getMessage(),
            ]);

            if ($this->consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
                $this->tripped = true;

                // Surface the underlying transport error once per batch so it
                // reaches Sentry, rather than once per follower.
                report($e);

                Log::error('BestEffortMailer: mail transport looks unavailable, skipping the rest of this batch', $context + [
                    'consecutive_failures' => $this->consecutiveFailures,
                ]);
            }

            return false;
        }
    }

    /**
     * True once the breaker has tripped and remaining sends are being skipped.
     */
    public function tripped(): bool
    {
        return $this->tripped;
    }

    /**
     * Log the batch outcome, but only when something actually went wrong.
     * Call once after a batch; safe to call when everything succeeded.
     *
     * @param array<string, mixed> $context identifying fields for the log line
     */
    public function logSummary(string $source, array $context = []): void
    {
        if (0 === $this->failed && 0 === $this->skipped) {
            return;
        }

        Log::warning($source.': some notification emails were not sent', $context + $this->summary());
    }

    /**
     * Counts for the caller to log once the batch is done.
     *
     * @return array{sent: int, failed: int, skipped: int, tripped: bool}
     */
    public function summary(): array
    {
        return [
            'sent' => $this->sent,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'tripped' => $this->tripped,
        ];
    }
}
