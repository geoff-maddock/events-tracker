<?php

namespace App\Jobs\Instagram;

use App\Jobs\Concerns\TracksJobStatus;
use App\Services\Integrations\InstagramEventPoster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Queued job that posts the weekend preview to Instagram: every public event
 * of the upcoming weekend, each as an individual story, in start-time order.
 *
 * The events go out in batches (#2159). The first job builds the list at run
 * time, posts the first InstagramEventPoster::WEEKEND_PREVIEW_BATCH_SIZE, and
 * queues the next job with the rest, and so on. A batch where nothing could be
 * posted fails, which also stops the remaining batches.
 */
class PostWeekendPreviewToInstagram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
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
        public ?int $userId,
        public ?array $eventIds = null,
        public int $batch = 1,
        public int $batches = 0,
    ) {
        $label = 'Instagram weekend preview'.($batches > 1 ? " (batch {$batch} of {$batches})" : '');
        $this->initJobStatus('instagram_weekend_preview', $label, null, $userId);
    }

    public function handle(InstagramEventPoster $poster): void
    {
        $this->markRunning();

        $eventIds = $this->eventIds ?? $poster->weekendPreviewEventIds();
        $batches = $this->batches > 0
            ? $this->batches
            : (int) ceil(count($eventIds) / InstagramEventPoster::WEEKEND_PREVIEW_BATCH_SIZE);

        $current = array_slice($eventIds, 0, InstagramEventPoster::WEEKEND_PREVIEW_BATCH_SIZE);
        $remaining = array_slice($eventIds, InstagramEventPoster::WEEKEND_PREVIEW_BATCH_SIZE);

        $result = $poster->postWeekendPreview($this->userId, $current);

        if ($remaining !== []) {
            self::dispatch($this->userId, $remaining, $this->batch + 1, $batches);
        }

        $message = ($batches > 1 ? "Batch {$this->batch} of {$batches}: " : '')
            .'Weekend preview posted: '.$result['posted'].' stor'
            .($result['posted'] === 1 ? 'y' : 'ies').' published'
            .($result['skipped'] > 0 ? ', '.$result['skipped'].' skipped (no photo).' : '.')
            .($remaining !== [] ? ' The next '.min(count($remaining), InstagramEventPoster::WEEKEND_PREVIEW_BATCH_SIZE).' are queued.' : '');

        $this->markSucceeded($message, $result + ['batch' => $this->batch, 'batches' => $batches]);
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }
}
