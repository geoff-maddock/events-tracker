<?php

namespace App\Services;

/**
 * What happened when an entity's update summary was sent to its contact.
 */
final class EntitySummaryResult
{
    public const SENT = 'sent';
    /** the entity has no contact with an email address */
    public const NO_CONTACT = 'no-contact';
    /** the contact has unsubscribed from entity emails */
    public const OPTED_OUT = 'opted-out';
    /** the email could not be handed to the mail transport */
    public const FAILED = 'failed';

    public function __construct(
        public readonly string $outcome,
        public readonly ?string $email = null,
    ) {
    }
}
