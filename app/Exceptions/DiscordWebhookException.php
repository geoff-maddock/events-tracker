<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * A failed Discord webhook call (issue #2058).
 *
 * The only distinction callers care about is whether retrying could ever help:
 *
 *  - permanent  — a malformed payload, or a webhook that no longer exists.
 *                 The job should fail immediately rather than burn its retry
 *                 budget, and the target's failure streak advances.
 *  - retryable  — a timeout, a 5xx, or rate limiting the client could not
 *                 absorb in process. The queue backs off and tries again, and
 *                 the target's health is left alone (counting these would take
 *                 every channel offline during a single Discord outage).
 *
 * The message must never contain the webhook URL — it is a bearer credential
 * and this string reaches logs, the ledger, and admin screens.
 */
class DiscordWebhookException extends Exception
{
    public function __construct(
        string $message,
        public readonly bool $permanent = false,
        public readonly ?int $status = null,
        public readonly ?int $discordCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    /**
     * Discord error code 10015, "Unknown Webhook" — the webhook was deleted on
     * their side. Nothing will ever make this call succeed, so the target is
     * disabled on the first occurrence rather than after a streak.
     */
    public function isUnknownWebhook(): bool
    {
        return 10015 === $this->discordCode
            || in_array($this->status, [401, 403, 404], true);
    }
}
