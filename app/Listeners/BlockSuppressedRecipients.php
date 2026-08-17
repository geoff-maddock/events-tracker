<?php

namespace App\Listeners;

use App\Models\EmailSuppression;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;

/**
 * Drops suppressed addresses from outgoing mail.
 *
 * Hooked on MessageSending rather than added to the send call sites because
 * Mailer::sendSymfonyMessage() treats a false return from this event as
 * "do not send", which makes it the only hook that covers *every* path —
 * including the Notify/NotifyWeekly console digests, which account for most
 * of the volume and never touch BestEffortMailer.
 *
 * Partially-suppressed messages have the bad recipients removed and are still
 * delivered to the rest; a message with nothing left is cancelled outright.
 */
class BlockSuppressedRecipients
{
    /**
     * @return bool false cancels the send
     */
    public function handle(MessageSending $event): bool
    {
        $message = $event->message;

        $to = $message->getTo();
        if ([] === $to) {
            return true;
        }

        $keep = [];
        $blocked = [];

        foreach ($to as $address) {
            /* @var Address $address */
            if (EmailSuppression::isSuppressed($address->getAddress())) {
                $blocked[] = $address->getAddress();
            } else {
                $keep[] = $address;
            }
        }

        if ([] === $blocked) {
            return true;
        }

        if ([] === $keep) {
            Log::info('BlockSuppressedRecipients: cancelled a message with no deliverable recipients', [
                'blocked' => $blocked,
                'subject' => $message->getSubject(),
            ]);

            return false;
        }

        $message->to(...$keep);

        Log::info('BlockSuppressedRecipients: dropped suppressed recipients from a message', [
            'blocked' => $blocked,
            'remaining' => count($keep),
            'subject' => $message->getSubject(),
        ]);

        return true;
    }
}
