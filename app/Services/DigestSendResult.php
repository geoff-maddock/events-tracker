<?php

namespace App\Services;

/**
 * What happened when a digest was sent to one user on request.
 */
enum DigestSendResult
{
    case Sent;
    /** the user has this digest switched off */
    case Disabled;
    /** the digest and its fallback were both empty, so no email went out */
    case NothingToSend;
    /** the email could not be handed to the mail transport */
    case Failed;
}
