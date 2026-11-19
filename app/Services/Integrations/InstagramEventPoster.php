<?php

namespace App\Services\Integrations;

use App\Models\Activity;
use App\Models\Event;
use App\Models\Tag;
use App\Models\EventShare;
use App\Models\Visibility;
use App\Services\ImageHandler;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Storage;

/**
 * Orchestrates publishing events to Instagram (feed, carousel, story, week).
 *
 * Extracted from EventInstagramController so the same logic can run inside a
 * queued job. Each public method returns the published Instagram media id, or
 * throws RuntimeException with a user-facing message on failure.
 */
class InstagramEventPoster extends InstagramPoster
{
    /**
     * Instagram permits at most 10 items in a single carousel. Handing more
     * than that to the createCarousel endpoint makes the whole call fail with
     * an opaque "No data returned" error (EVENTREPO-X9), so the container list
     * is trimmed to this ceiling before publishing.
     */
    private const MAX_CAROUSEL_ITEMS = 10;

    // Instagram caps how many stories are worth pushing in one run.
    private const PREVIEW_STORY_LIMIT = 10;

    /**
     * Weekend preview stories posted per queued job: each needs an upload, a
     * status poll and a publish, so one job per batch stays inside its timeout.
     */
    public const WEEKEND_PREVIEW_BATCH_SIZE = 10;

    // Instagram's caption length limit
    private const CAPTION_LIMIT = 2200;

    // a short run's dates in the weekly carousel caption, which carries up to nine events (#2375)
    private const WEEK_SHOWINGS_LISTED = 3;

    /**
     * Post a single event photo to the Instagram feed.
     */
    public function postSingle(Event $event, ?int $userId): int
    {
        $this->assertCredentials();

        $imageUrl = $this->photoUrl($event->getPrimaryPhoto());
        $result = $this->publishSinglePhoto($imageUrl, urlEncode($event->getInstagramFormat()));

        $this->recordShare($event, $result, $userId);

        return $result;
    }

    /**
     * Post an event (plus related photos) as a carousel to the Instagram feed.
     */
    public function postCarousel(Event $event, ?int $userId): int
    {
        $this->assertCredentials();

        $imageUrl = $this->photoUrl($event->getPrimaryPhoto());
        $caption = $event->getInstagramFormat();

        $igContainerIds = [$this->uploadCarouselItem($imageUrl)];

        // Additional photos directly attached to the event.
        foreach ($event->getOtherPhotos() as $otherPhoto) {
            if (count($igContainerIds) >= self::MAX_CAROUSEL_ITEMS) {
                Log::info('Carousel item limit (' . self::MAX_CAROUSEL_ITEMS . ') reached for event ' . $event->id . '; skipping remaining photos.');
                break;
            }

            $otherUrl = Storage::disk('external')->url($otherPhoto->getStoragePath());
            if (!$otherUrl) {
                continue;
            }

            $igContainerIds[] = $this->uploadCarouselItem($otherUrl);
        }

        // Primary photos of related entities — best effort, skip on failure.
        foreach ($event->entities as $entity) {
            foreach ($entity->photos as $photo) {
                if (count($igContainerIds) >= self::MAX_CAROUSEL_ITEMS) {
                    Log::info('Carousel item limit (' . self::MAX_CAROUSEL_ITEMS . ') reached for event ' . $event->id . '; skipping remaining entity photos.');
                    break 2;
                }

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

        $result = $this->publishCarousel($igContainerIds, $caption);

        $this->recordShare($event, $result, $userId);

        return $result;
    }

    /**
     * Post an event photo as an Instagram story.
     */
    public function postStory(Event $event, ?int $userId): int
    {
        $this->assertCredentials();

        $imageUrl = $this->photoUrl($event->getPrimaryPhoto());
        $caption = urlEncode($event->getInstagramFormat());
        $result = $this->publishStoryPhoto($imageUrl, $caption, route('events.show', $event->id));

        $this->recordShare($event, $result, $userId);

        return $result;
    }

    /**
     * Post this week's events as one carousel: a generated cover image, then
     * the primary photo of each of the first nine public, uncancelled events,
     * with each event's details in the caption. Events without a photo are
     * left out.
     */
    public function postWeek(ImageHandler $imageHandler, ?int $userId): int
    {
        $this->assertCredentials();

        // the cover takes one of the carousel's slots; a short run is in any
        // week it plays (#2375)
        $events = Event::query()
            ->occurringBetween(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek())
            ->where('visibility_id', '=', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('cancelled_at')
            ->orderByNextStart(Carbon::now()->startOfWeek())
            ->with('showings')
            ->limit(self::MAX_CAROUSEL_ITEMS - 1)
            ->get();

        // find the events with a usable photo before generating and uploading anything
        $caption = "Events for the upcoming week...\n";
        $included = [];
        $imageUrls = [];

        foreach ($events as $event) {
            try {
                $imageUrls[] = $this->photoUrl($event->getPrimaryPhoto());
            } catch (RuntimeException $e) {
                Log::info('Week post: skipping event '.$event->id.': '.$e->getMessage());
                continue;
            }

            // a short run's first few dates, so it can't crowd the other events out of the caption
            $caption .= $event->getInstagramFormat(self::WEEK_SHOWINGS_LISTED)."\n\n";
            $included[] = $event;
        }

        if ($included === []) {
            throw new RuntimeException('None of this week\'s events have a photo to post to Instagram.');
        }

        $coverFileName = 'week-image.jpg';
        $coverImagePath = $imageHandler->generateCoverImage($coverFileName);
        if (!is_file($coverImagePath)) {
            throw new RuntimeException('You must have a base image to make a week post to Instagram.');
        }

        $coverPath = Storage::disk('external')->putFileAs('photos', new HttpFile($coverImagePath), $coverFileName, 'public');
        $igContainerIds = [$this->uploadCarouselItem(Storage::disk('external')->url($coverPath))];

        foreach ($imageUrls as $imageUrl) {
            $igContainerIds[] = $this->uploadCarouselItem($imageUrl);
        }

        $result = $this->publishCarousel($igContainerIds, $caption);

        foreach ($included as $event) {
            $this->recordEventShare($event, $result, $userId);
        }

        return $result;
    }

    private function recordShare(Event $event, int $mediaId, ?int $userId): void
    {
        Activity::log($event, $userId ? \App\Models\User::find($userId) : null, 16);

        $this->recordEventShare($event, $mediaId, $userId);
    }

    private function recordEventShare(Event $event, int $mediaId, ?int $userId): void
    {
        EventShare::create([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => (string) $mediaId,
            'created_by' => $userId,
            'posted_at' => Carbon::now(),
        ]);
    }

    /**
     * Every public, uncancelled event of the upcoming weekend (Friday 00:00
     * through Sunday 23:59) that has a primary photo, in start-time order, so
     * the stories run from Friday to Sunday. PostWeekendPreviewToInstagram
     * posts them in batches of WEEKEND_PREVIEW_BATCH_SIZE (#2159).
     *
     * Events without a photo can't be a story, and leaving them out here keeps
     * every batch postable: a batch that posts nothing then means Instagram is
     * failing, which stops the remaining batches, rather than a run of
     * photo-less events stopping them.
     *
     * @return array<int, int>
     */
    public function weekendPreviewEventIds(): array
    {
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

        // a short run playing that weekend is one story, placed by its first
        // showing there (#2375)
        $ids = Event::query()
            ->occurringBetween($fridayStart, $sundayEnd)
            ->where('visibility_id', '=', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('cancelled_at')
            // the photo getPrimaryPhoto() uses
            ->whereHas('photos', fn ($query) => $query->where('photos.is_primary', '=', 1))
            ->orderByNextStart($fridayStart)
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($ids === []) {
            throw new RuntimeException('No events with a photo found for the upcoming weekend.');
        }

        return $ids;
    }

    /**
     * Post the given weekend events to Instagram Stories, one story each, in
     * the order given; with no ids, every event of the upcoming weekend.
     * Events made private or cancelled since the list was built are left out.
     *
     * Per-event failures (no photo, upload/status/publish errors) are logged
     * and skipped; the loop continues. Throws when nothing could be posted.
     *
     * @param array<int, int>|null $eventIds
     *
     * @return array{posted: int, skipped: int, total: int}
     */
    public function postWeekendPreview(?int $userId, ?array $eventIds = null): array
    {
        $this->assertCredentials();

        return $this->postEventStories($userId, $eventIds ?? $this->weekendPreviewEventIds(), 'Weekend preview');
    }

    /**
     * Post the given events to Instagram Stories, one story each, in the order
     * given. Events made private or cancelled since the list was built are
     * left out. Used by the weekend preview and the tag stories (#2287).
     *
     * Per-event failures (no photo, upload/status/publish errors) are logged
     * and skipped; the loop continues. Throws when nothing could be posted.
     *
     * @param array<int, int> $eventIds
     *
     * @return array{posted: int, skipped: int, total: int}
     */
    public function postEventStories(?int $userId, array $eventIds, string $context): array
    {
        $this->assertCredentials();

        $events = $this->publicEventsInOrder($eventIds);

        $posted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($events as $event) {
            try {
                $this->postStory($event, $userId);
                $posted++;
            } catch (Exception $e) {
                Log::info($context.': skipping event '.$event->id.': '.$e->getMessage());
                $errors[] = $e->getMessage();
                $skipped++;
            }
        }

        if ($posted === 0) {
            throw new RuntimeException('No stories could be posted.'.$this->noStoriesReason($errors));
        }

        return ['posted' => $posted, 'skipped' => $skipped, 'total' => $events->count()];
    }

    /**
     * Every public, uncancelled upcoming event with this tag that has a primary
     * photo, soonest first: what a tag's Instagram posts draw from (#2287).
     *
     * @return array<int, int>
     */
    public function tagEventIds(Tag $tag): array
    {
        $ids = $tag->events()
            // a short run until its last showing, by its next one (#2375)
            ->occurringBetween(Carbon::now())
            ->where('events.visibility_id', '=', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('events.cancelled_at')
            // the photo getPrimaryPhoto() uses
            ->whereHas('photos', fn ($query) => $query->where('photos.is_primary', '=', 1))
            ->orderByNextStart(Carbon::now())
            ->orderBy('events.id', 'asc')
            ->pluck('events.id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            throw new RuntimeException('No upcoming events with a photo are tagged '.$tag->name.'.');
        }

        return $ids;
    }

    /**
     * Post a tag's next upcoming events to the Instagram feed: a carousel of
     * up to ten event photos (a single photo when there's only one), soonest
     * first, with one short line per event in the caption (#2287).
     */
    public function postTagCarousel(Tag $tag, ?int $userId): int
    {
        $this->assertCredentials();

        $events = $this->publicEventsInOrder(array_slice($this->tagEventIds($tag), 0, self::MAX_CAROUSEL_ITEMS));

        // event photos are best effort: skip any that fail to upload
        $igContainerIds = [];
        $included = [];
        foreach ($events as $event) {
            try {
                $imageUrl = $this->photoUrl($event->getPrimaryPhoto());
                $igContainerIds[] = count($events) === 1 ? 0 : $this->uploadCarouselItem($imageUrl);
                $included[] = [$event, $imageUrl];
            } catch (Exception $e) {
                Log::info('Tag post: skipping event '.$event->id.': '.$e->getMessage());
            }
        }

        if ($included === []) {
            throw new RuntimeException('None of the upcoming events tagged '.$tag->name.' could be uploaded to Instagram.');
        }

        $caption = $this->tagCaption($tag, array_column($included, 0));

        $result = count($included) === 1
            ? $this->publishSinglePhoto($included[0][1], urlEncode($caption))
            : $this->publishCarousel($igContainerIds, $caption);

        foreach ($included as [$event]) {
            $this->recordEventShare($event, $result, $userId);
        }

        return $result;
    }

    /**
     * One short line per event. Instagram caps captions at 2,200 characters,
     * and the caption goes out URL-encoded (createCarousel cuts the encoded
     * string at 2,200), so lines are added while the encoded caption fits.
     *
     * @param array<int, Event> $events
     */
    private function tagCaption(Tag $tag, array $events): string
    {
        $caption = 'Upcoming '.$tag->name." events\n";
        $footer = "\n\nMore at ".route('tags.show', $tag->slug);

        foreach ($events as $event) {
            // a short run by its next date, and that it has more (#2375)
            $more = null !== $event->showtimesSummary() ? ' (+ more dates)' : '';
            $line = "\n".$event->display_start_at?->format('D M j').' - '.$event->name.($event->venueLabel() ? ' @ '.$event->venueLabel() : '').$more;

            if (strlen(urlencode($caption.$line.$footer)) > self::CAPTION_LIMIT) {
                break;
            }
            $caption .= $line;
        }

        return $caption.$footer;
    }

    /**
     * The given events that are still public and not cancelled, in the given order.
     *
     * @param array<int, int> $eventIds
     *
     * @return \Illuminate\Support\Collection<int, Event>
     */
    private function publicEventsInOrder(array $eventIds): \Illuminate\Support\Collection
    {
        return Event::whereIn('id', $eventIds)
            ->where('visibility_id', '=', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('cancelled_at')
            ->with('showings')
            ->get()
            ->sortBy(fn (Event $event): int|false => array_search($event->id, $eventIds, true))
            ->values();
    }

    /**
     * Post a preview of today's events to Instagram Stories: the events
     * starting today, each as an individual story.
     *
     * Selection rules:
     *  - Rank today's events by attending-response count and take the top 10.
     *  - Re-sort that selection by start time, so the stories publish in the
     *    order the events actually happen.
     *
     * The window covers the whole day (00:00 through 23:59), so events that
     * have already started are still included.
     *
     * Per-event failures (no photo, upload/status/publish errors) are logged
     * and skipped; the loop continues. Throws only for terminal cases.
     *
     * @return array{posted: int, skipped: int, total: int}
     */
    public function postTodaysPreview(?int $userId): array
    {
        $this->assertCredentials();

        $dayStart = Carbon::today()->startOfDay();
        $dayEnd = Carbon::today()->endOfDay();

        // Fetch all of today's events ranked by number of attending responses,
        // excluding cancelled and non-public events.
        // a short run with a showing today is in today's preview (#2375)
        $todaysEvents = Event::query()
            ->occurringBetween($dayStart, $dayEnd)
            ->where('visibility_id', '=', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('cancelled_at')
            ->withCount(['eventResponses as response_count'])
            ->orderBy('response_count', 'desc')
            ->orderByNextStart($dayStart)
            ->with('showings')
            ->get();

        if ($todaysEvents->isEmpty()) {
            throw new RuntimeException('No events found for today.');
        }

        // Response count decides which events make the cut; start time decides
        // the order they go out in.
        $selectedEvents = $todaysEvents->take(self::PREVIEW_STORY_LIMIT)
            ->sortBy(fn (Event $event) => $event->occurrencesBetween($dayStart, $dayEnd)->first()?->start_at->getTimestamp())
            ->values();

        $posted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($selectedEvents as $event) {
            try {
                $this->postStory($event, $userId);
                $posted++;
            } catch (Exception $e) {
                Log::info("Today's preview: skipping event ".$event->id.': '.$e->getMessage());
                $errors[] = $e->getMessage();
                $skipped++;
            }
        }

        if ($posted === 0) {
            throw new RuntimeException('No stories could be posted.'.$this->noStoriesReason($errors));
        }

        return ['posted' => $posted, 'skipped' => $skipped, 'total' => $selectedEvents->count()];
    }

    /**
     * The reason to append when no stories could be posted. Surfaces the actual
     * per-event failure (e.g. an Instagram rate limit) rather than the
     * misleading "no photos" guidance — events reaching this point were already
     * selected for having a primary photo, so a hardcoded "ensure they have
     * photos" message hid the real cause in error reporting.
     *
     * @param array<int, string> $errors
     */
    private function noStoriesReason(array $errors): string
    {
        if ($errors === []) {
            return ' Ensure the selected events have photos.';
        }

        return ' '.end($errors);
    }
}
