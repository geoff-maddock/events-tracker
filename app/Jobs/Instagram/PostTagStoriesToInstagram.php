<?php

namespace App\Jobs\Instagram;

use App\Jobs\Concerns\TracksJobStatus;
use App\Models\Tag;
use App\Services\Integrations\InstagramEventPoster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Queued job that posts every upcoming event with a tag (and a photo) to
 * Instagram Stories, one story each, in start-time order (#2287).
 *
 * Batched like the weekend preview (#2159): the first job builds the list at
 * run time, posts the first InstagramEventPoster::WEEKEND_PREVIEW_BATCH_SIZE,
 * and queues the next job with the rest. A batch where nothing could be posted
 * fails, which also stops the remaining batches.
 */
class PostTagStoriesToInstagram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksJobStatus;

    // One batch of stories, each needing an upload, a status poll, and a publish.
    public int $timeout = 1200;

    // No retries: a retry after a partial success would double-post the
    // stories that already published.
    public int $tries = 1;

    /**
     * @param array<int, int>|null $eventIds the events still to post; null on the first batch
     */
    public function __construct(
        public Tag $tag,
        public ?int $userId,
        public ?array $eventIds = null,
        public int $batch = 1,
        public int $batches = 0,
    ) {
        $label = 'Instagram tag stories: '.$tag->name.($batches > 1 ? " (batch {$batch} of {$batches})" : '');
        $this->initJobStatus('instagram_story', $label, $tag, $userId);
    }

    public function handle(InstagramEventPoster $poster): void
    {
        $this->markRunning();

        $batchSize = InstagramEventPoster::WEEKEND_PREVIEW_BATCH_SIZE;
        $eventIds = $this->eventIds ?? $poster->tagEventIds($this->tag);
        $batches = $this->batches > 0 ? $this->batches : (int) ceil(count($eventIds) / $batchSize);

        $current = array_slice($eventIds, 0, $batchSize);
        $remaining = array_slice($eventIds, $batchSize);

        $result = $poster->postEventStories($this->userId, $current, 'Tag stories');

        if ($remaining !== []) {
            self::dispatch($this->tag, $this->userId, $remaining, $this->batch + 1, $batches);
        }

        $message = ($batches > 1 ? "Batch {$this->batch} of {$batches}: " : '')
            .$this->tag->name.' stories posted: '.$result['posted'].' stor'
            .($result['posted'] === 1 ? 'y' : 'ies').' published'
            .($result['skipped'] > 0 ? ', '.$result['skipped'].' skipped.' : '.')
            .($remaining !== [] ? ' The next '.min(count($remaining), $batchSize).' are queued.' : '');

        $this->markSucceeded($message, $result + ['batch' => $this->batch, 'batches' => $batches]);
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }
}
