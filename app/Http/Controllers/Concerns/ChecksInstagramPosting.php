<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\User;
use App\Models\Visibility;
use App\Services\Integrations\Instagram;
use Carbon\Carbon;

/**
 * Pre-flight checks shared by the web and API Instagram posting actions. Each
 * returns a user-facing error string, or null when posting may proceed.
 */
trait ChecksInstagramPosting
{
    /**
     * Quick pre-flight check so callers fail fast when Instagram is not linked.
     * Returns a user-facing error string, or null when credentials are present.
     */
    private function instagramCredentialError(Instagram $instagram): ?string
    {
        if (!$instagram->getIgUserId()) {
            return 'You must have an Instagram user account linked to post to Instagram.';
        }

        if (!$instagram->getPageAccessToken()) {
            return 'You must have an Instagram page linked to post to Instagram.';
        }

        return null;
    }


    /**
     * Manual reposts are throttled: an event that already went to Instagram
     * within the last three days may not be posted again. Admins are exempt.
     * Returns a user-facing error string, or null when posting may proceed.
     */
    private function recentRepostError(Event $event, ?User $user): ?string
    {
        if ($user && $user->isAdmin()) {
            return null;
        }

        $lastShare = EventShare::where('event_id', $event->id)
            ->where('platform', 'instagram')
            ->whereNotNull('posted_at')
            ->where('posted_at', '>=', Carbon::now()->subDays(3))
            ->orderBy('posted_at', 'desc')
            ->first();

        if ($lastShare) {
            return sprintf(
                'This event was already posted to Instagram %s. Events can only be reposted three days after the last post.',
                $lastShare->posted_at->diffForHumans()
            );
        }

        return null;
    }


    /**
     * Instagram posts are built from the event's primary photo. Fail fast at
     * dispatch time when it is missing so the caller gets an immediate,
     * actionable message, instead of queuing a job that throws "You must have
     * a photo…" and burns all three retries before giving up (EVENTREPO-VA).
     */
    private function eventPhotoError(Event $event): ?string
    {
        if (!$event->getPrimaryPhoto()) {
            return 'This event must have a primary photo before it can be posted to Instagram.';
        }

        return null;
    }


    /**
     * Only public events may go to the site's Instagram, and only their owner or an admin may send them.
     * Mirrors the checks in Api\EventInstagramController::postCarouselToInstagramApi.
     */
    private function eventShareError(Event $event, ?User $user): ?string
    {
        if (!$user) {
            return 'You must be signed in to post to Instagram.';
        }

        if ($event->visibility_id !== Visibility::VISIBILITY_PUBLIC) {
            return 'Only public events can be posted to Instagram.';
        }

        $isOwner = (int) $event->created_by === $user->id;
        $isAdmin = $user->hasGroup('admin') || $user->hasGroup('super_admin');

        return ($isOwner || $isAdmin) ? null : 'You are not authorized to post this event to Instagram.';
    }
}
