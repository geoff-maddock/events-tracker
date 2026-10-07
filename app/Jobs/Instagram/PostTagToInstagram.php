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
 * Queued job that posts a tag's next upcoming events to the Instagram feed as
 * one carousel (#2287).
 */
class PostTagToInstagram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksJobStatus;

    // Up to ten uploads, each polled until ready.
    public int $timeout = 1200;

    // No retries: a retry after the carousel published would post it twice.
    public int $tries = 1;

    public function __construct(
        public Tag $tag,
        public ?int $userId
    ) {
        $this->initJobStatus('instagram_post', 'Instagram tag post: '.$tag->name, $tag, $userId);
    }

    public function handle(InstagramEventPoster $poster): void
    {
        $this->markRunning();

        $mediaId = $poster->postTagCarousel($this->tag, $this->userId);

        $this->markSucceeded(
            'Successfully published upcoming '.$this->tag->name.' events to Instagram (media id: '.$mediaId.').',
            ['media_id' => $mediaId]
        );
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }
}
