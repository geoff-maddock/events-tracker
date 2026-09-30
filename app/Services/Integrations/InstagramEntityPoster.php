<?php

namespace App\Services\Integrations;

use App\Models\Activity;
use App\Models\Entity;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Publishes an entity (venue, artist, promoter…) to Instagram, as a feed post
 * or a story. Each public method returns the published Instagram media id, or
 * throws RuntimeException with a user-facing message on failure.
 */
class InstagramEntityPoster extends InstagramPoster
{
    // the entity's own photo takes the carousel's first slot
    private const MAX_EVENT_PHOTOS = 9;

    /**
     * Post the entity's photo followed by the primary photos of its next nine
     * events as a carousel, or the entity's photo alone when none of its
     * upcoming events has a usable photo.
     */
    public function postToFeed(Entity $entity, ?User $user): int
    {
        $this->assertCredentials();

        $entityImageUrl = $this->photoUrl($entity->getPrimaryPhoto());
        $caption = $this->caption($entity);

        $igContainerIds = [$this->uploadCarouselItem($entityImageUrl)];

        // other people's private events must not end up on Instagram
        $futureEvents = $entity->events()
            ->distinct()
            ->visible(null)
            ->where('start_at', '>=', Carbon::now())
            ->orderBy('start_at', 'ASC')
            ->limit(self::MAX_EVENT_PHOTOS)
            ->get();

        // event photos are best effort: skip any that are missing or fail
        foreach ($futureEvents as $event) {
            try {
                $igContainerIds[] = $this->instagram->uploadCarouselPhoto($this->photoUrl($event->getPrimaryPhoto()));
            } catch (Exception $e) {
                Log::info('Entity instagram carousel: skipping event '.$event->id.': '.$e->getMessage());
            }
        }

        $result = count($igContainerIds) === 1
            ? $this->publishSinglePhoto($entityImageUrl, urlEncode($caption))
            : $this->publishCarousel($igContainerIds, $caption);

        Activity::log($entity, $user, 16);

        return $result;
    }

    /**
     * Post the entity's primary photo as a story.
     */
    public function postStory(Entity $entity, ?User $user): int
    {
        $this->assertCredentials();

        $imageUrl = $this->photoUrl($entity->getPrimaryPhoto());
        $result = $this->publishStoryPhoto($imageUrl, urlEncode($this->caption($entity)));

        Activity::log($entity, $user, 16);

        return $result;
    }

    /**
     * The caption, including the entity's upcoming events.
     */
    private function caption(Entity $entity): string
    {
        $caption = $entity->getInstagramFormat();
        if (!$caption) {
            throw new RuntimeException('You must have an Instagram caption to post to Instagram.');
        }

        return $caption;
    }
}
