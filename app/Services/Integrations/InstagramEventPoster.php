<?php

namespace App\Services\Integrations;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventShare;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Storage;

/**
 * Orchestrates publishing events to Instagram (feed, carousel, story).
 *
 * Extracted from EventInstagramController so the same logic can run inside a
 * queued job. Each public method returns the published Instagram media id, or
 * throws RuntimeException with a user-facing message on failure.
 */
class InstagramEventPoster
{
    public function __construct(private Instagram $instagram)
    {
    }

    /**
     * Post a single event photo to the Instagram feed.
     */
    public function postSingle(Event $event, ?int $userId): int
    {
        $this->assertCredentials();

        $imageUrl = $this->primaryImageUrl($event);
        $caption = urlEncode($event->getInstagramFormat());

        try {
            $igContainerId = $this->instagram->uploadPhoto($imageUrl, $caption);
        } catch (Exception $e) {
            Log::info('Instagram single post upload error: ' . $e->getMessage());
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        if ($this->instagram->checkStatus($igContainerId) === false) {
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        $result = $this->instagram->publishMedia($igContainerId);
        if ($result === false) {
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        $this->recordShare($event, (int) $result, $userId);

        return (int) $result;
    }

    /**
     * Post an event (plus related photos) as a carousel to the Instagram feed.
     */
    public function postCarousel(Event $event, ?int $userId): int
    {
        $this->assertCredentials();

        $imageUrl = $this->primaryImageUrl($event);
        $caption = $event->getInstagramFormat();

        $igContainerIds = [];

        try {
            $igContainerIds[] = $this->instagram->uploadCarouselPhoto($imageUrl);
        } catch (Exception $e) {
            Log::info('Carousel photo error: ' . $e->getMessage());
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        // Additional photos directly attached to the event.
        foreach ($event->getOtherPhotos() as $otherPhoto) {
            $otherUrl = Storage::disk('external')->url($otherPhoto->getStoragePath());
            if (!$otherUrl) {
                continue;
            }

            try {
                $igContainerIds[] = $this->instagram->uploadCarouselPhoto($otherUrl);
            } catch (Exception $e) {
                Log::info('Carousel photo error: ' . $e->getMessage());
                throw new RuntimeException('There was an error posting to Instagram. Please try again.');
            }
        }

        // Primary photos of related entities — best effort, skip on failure.
        foreach ($event->entities as $entity) {
            foreach ($entity->photos as $photo) {
                if (!$photo->is_primary) {
                    continue;
                }

                $entityUrl = Storage::disk('external')->url($photo->getStoragePath());
                if (!$entityUrl) {
                    continue;
                }

                try {
                    $igContainerIds[] = $this->instagram->uploadCarouselPhoto($entityUrl);
                } catch (Exception $e) {
                    Log::info('Error uploading carousel photo for entity ' . $entity->id . ', skipping: ' . $e->getMessage());
                }
            }
        }

        if ($this->instagram->checkBatchStatus($igContainerIds) === false) {
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        try {
            $igCarouselId = $this->instagram->createCarousel($igContainerIds, $caption);
        } catch (Exception $e) {
            Log::info('Error creating carousel: ' . $e->getMessage());
            throw new RuntimeException('There was an error posting carousel to Instagram. Please try again.');
        }

        if ($this->instagram->checkStatus($igCarouselId) === false) {
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        $result = $this->instagram->publishMedia($igCarouselId);
        if ($result === false) {
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        $this->recordShare($event, (int) $result, $userId);

        return (int) $result;
    }

    /**
     * Post an event photo as an Instagram story.
     */
    public function postStory(Event $event, ?int $userId): int
    {
        $this->assertCredentials();

        $imageUrl = $this->primaryImageUrl($event);
        $caption = urlEncode($event->getInstagramFormat());
        $eventUrl = route('events.show', $event->id);

        try {
            $igContainerId = $this->instagram->uploadStoryPhoto($imageUrl, $caption, $eventUrl);
        } catch (Exception $e) {
            Log::info('Story photo upload error: ' . $e->getMessage());
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        if ($this->instagram->checkStatus($igContainerId) === false) {
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        $result = $this->instagram->publishStoryMedia($igContainerId);
        if ($result === false) {
            throw new RuntimeException('There was an error posting to Instagram. Please try again.');
        }

        $this->recordShare($event, (int) $result, $userId);

        return (int) $result;
    }

    /**
     * Verify the linked Instagram account is usable before any uploads happen.
     */
    private function assertCredentials(): void
    {
        if (!$this->instagram->getIgUserId()) {
            throw new RuntimeException('You must have an Instagram user account linked to post to Instagram.');
        }

        if (!$this->instagram->getPageAccessToken()) {
            throw new RuntimeException('You must have an Instagram page linked to post to Instagram.');
        }
    }

    private function primaryImageUrl(Event $event): string
    {
        $photo = $event->getPrimaryPhoto();
        if (!$photo) {
            throw new RuntimeException('You must have a photo to extract the image to post to Instagram.');
        }

        $imageUrl = Storage::disk('external')->url($photo->getStoragePath());
        if (!$imageUrl) {
            throw new RuntimeException('You must have an image url to post to Instagram.');
        }

        return $imageUrl;
    }

    private function recordShare(Event $event, int $mediaId, ?int $userId): void
    {
        Activity::log($event, $userId ? \App\Models\User::find($userId) : null, 16);

        EventShare::create([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => (string) $mediaId,
            'created_by' => $userId,
            'posted_at' => Carbon::now(),
        ]);
    }
}
