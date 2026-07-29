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
 * Queued job that posts the weekend preview (top weekend events, each as an
 * individual story) to Instagram. Event selection happens at run time inside
 * InstagramEventPoster::postWeekendPreview(), so there is no subject model.
 */
class PostWeekendPreviewToInstagram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use TracksJobStatus;

    // Up to 10 stories, each needing an upload, a status poll, and a publish.
    public int $timeout = 1200;

    // No retries: a retry after a partial success would double-post the
    // stories that already published.
    public int $tries = 1;

    public function __construct(
        public ?int $userId
    ) {
        $this->initJobStatus('instagram_weekend_preview', 'Instagram weekend preview', null, $userId);
    }

    public function handle(InstagramEventPoster $poster): void
    {
        $this->markRunning();

        $result = $poster->postWeekendPreview($this->userId);

        $message = 'Weekend preview posted: '.$result['posted'].' stor'
            .($result['posted'] === 1 ? 'y' : 'ies').' published'
            .($result['skipped'] > 0 ? ', '.$result['skipped'].' skipped (no photo).' : '.');

        $this->markSucceeded($message, $result);
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }
}
