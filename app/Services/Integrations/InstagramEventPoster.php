<?php

namespace App\Services\Integrations;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Visibility;
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

    private function failureMessage(string $stage): string
    {
        $detail = $this->instagram->getLastError();
        $base = 'There was an error posting to Instagram during '.$stage.'.';
        if ($detail) {
            Log::error('Instagram '.$stage.' failed: '.$detail);
            return $base.' '.$detail;
        }
        return $base.' Please try again.';
    }

    /**
     * Wrap a thrown SDK/HTTP exception from an upload/create call.
     *
     * These call-sites previously threw a hardcoded "Please try again." message
     * and logged the real cause only at info level, so every distinct Instagram
     * failure (rate limit, rejected image, expired token, …) collapsed into one
     * opaque Sentry issue (EVENTREPO-VB). Surfacing the underlying message and
     * chaining the original exception lets Sentry group by actual cause and
     * preserves the full stack trace for triage.
     */
    private function uploadFailure(string $stage, Exception $e): RuntimeException
    {
        Log::error('Instagram '.$stage.' failed: '.$e->getMessage());

        return new RuntimeException(
            'There was an error posting to Instagram during '.$stage.'. '.$e->getMessage(),
            0,
            $e
        );
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
            throw $this->uploadFailure('single photo upload', $e);
        }

        if ($this->instagram->checkStatus($igContainerId) === false) {
            throw new RuntimeException($this->failureMessage('single photo status check'));
        }

        $result = $this->instagram->publishMedia($igContainerId);
        if ($result === false) {
            throw new RuntimeException($this->failureMessage('single photo publish'));
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
            throw $this->uploadFailure('carousel photo upload', $e);
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
                throw $this->uploadFailure('carousel photo upload', $e);
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
            throw new RuntimeException($this->failureMessage('carousel batch status check'));
        }

        try {
            $igCarouselId = $this->instagram->createCarousel($igContainerIds, $caption);
        } catch (Exception $e) {
            throw $this->uploadFailure('carousel creation', $e);
        }

        if ($this->instagram->checkStatus($igCarouselId) === false) {
            throw new RuntimeException($this->failureMessage('carousel status check'));
        }

        $result = $this->instagram->publishMedia($igCarouselId);
        if ($result === false) {
            throw new RuntimeException($this->failureMessage('carousel publish'));
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
            throw $this->uploadFailure('story photo upload', $e);
        }

        if ($this->instagram->checkStatus($igContainerId) === false) {
            throw new RuntimeException($this->failureMessage('story status check'));
        }

        $result = $this->instagram->publishStoryMedia($igContainerId);
        if ($result === false) {
            throw new RuntimeException($this->failureMessage('story publish'));
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

    /**
     * Post a weekend preview to Instagram Stories: the top events for the
     * upcoming weekend (Fri–Sun), ranked by attending-response count.
     *
     * Selection rules:
     *  - If <= 10 weekend events: post all of them.
     *  - If > 10: rank by response count descending and take top 10.
     *  - If the 10th and 11th events are tied (no clear cutoff): fall back to
     *    5 from Friday + 5 from Saturday, each sorted by response count.
     *
     * Per-event failures (no photo, upload/status/publish errors) are logged
     * and skipped; the loop continues. Throws only for terminal cases.
     *
     * @return array{posted: int, skipped: int, total: int}
     */
    public function postWeekendPreview(?int $userId): array
    {
        $this->assertCredentials();

        // Determine the upcoming weekend window (Friday 00:00 through Sunday 23:59)
        $today = Carbon::today();

        if ($today->isFriday()) {
            $fridayStart = $today->copy()->startOfDay();
        } elseif ($today->isSaturday() || $today->isSunday()) {
            $fridayStart = $today->copy()->previous(Carbon::FRIDAY)->startOfDay();
        } else {
            // Mon–Thu: look to the coming Friday
            $fridayStart = $today->copy()->next(Carbon::FRIDAY)->startOfDay();
        }

        $sundayEnd = $fridayStart->copy()->next(Carbon::SUNDAY)->endOfDay();

        // Fetch all weekend events ranked by number of attending responses,
        // excluding cancelled and non-public events.
        $allWeekendEvents = Event::where('start_at', '>=', $fridayStart)
            ->where('start_at', '<=', $sundayEnd)
            ->where('visibility_id', '=', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('cancelled_at')
            ->withCount(['eventResponses as response_count'])
            ->orderBy('response_count', 'desc')
            ->orderBy('start_at', 'asc')
            ->get();

        if ($allWeekendEvents->isEmpty()) {
            throw new RuntimeException('No events found for the upcoming weekend.');
        }

        if ($allWeekendEvents->count() <= 10) {
            $selectedEvents = $allWeekendEvents;
        } else {
            // A clear response-count cutoff between position 10 and 11 lets us
            // take a clean top 10; a tie falls back to day-based distribution.
            $tenth = $allWeekendEvents->get(9);
            $eleventh = $allWeekendEvents->get(10);

            if ($tenth && $eleventh && $tenth->response_count !== $eleventh->response_count) {
                $selectedEvents = $allWeekendEvents->take(10);
            } else {
                $fridayEvents = $allWeekendEvents
                    ->filter(fn ($e) => Carbon::parse($e->start_at)->isFriday())
                    ->take(5);
                $saturdayEvents = $allWeekendEvents
                    ->filter(fn ($e) => Carbon::parse($e->start_at)->isSaturday())
                    ->take(5);
                $selectedEvents = $fridayEvents->merge($saturdayEvents);
            }
        }

        if ($selectedEvents->isEmpty()) {
            throw new RuntimeException('No events selected for the weekend preview.');
        }

        $posted = 0;
        $skipped = 0;

        foreach ($selectedEvents as $event) {
            try {
                $this->postStory($event, $userId);
                $posted++;
            } catch (Exception $e) {
                Log::info('Weekend preview: skipping event '.$event->id.': '.$e->getMessage());
                $skipped++;
            }
        }

        if ($posted === 0) {
            throw new RuntimeException('No stories could be posted. Ensure the selected events have photos.');
        }

        return ['posted' => $posted, 'skipped' => $skipped, 'total' => $selectedEvents->count()];
    }
}
