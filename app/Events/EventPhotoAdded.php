<?php

namespace App\Events;

use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A photo was attached to an event (issue #2058).
 *
 * The trigger the Discord announcement actually needs. EventCreated fires
 * before most events have a flyer — the common flow is create, then upload —
 * and an announcement without an image is easy to scroll past.
 *
 * A Photo model observer would not work here: photos attach through
 * $event->addPhoto() on a belongsToMany, so there is no model event to hook
 * without introducing a pivot model.
 */
class EventPhotoAdded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Event $event,
        /** Whether the attached photo became the event's primary image. */
        public bool $isPrimary = false,
    ) {}
}
