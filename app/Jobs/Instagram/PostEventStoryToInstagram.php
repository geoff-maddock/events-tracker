<?php

namespace App\Jobs\Instagram;

use App\Jobs\Concerns\TracksJobStatus;
use App\Models\Event;
use App\Services\Integrations\InstagramEventPoster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Queued job that posts an event to Instagram Stories.
 */
class PostEventStoryToInstagram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksJobStatus;

    public int $timeout = 600;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 180];

    public function __construct(
        public Event $event,
        public ?int $userId
    ) {
        $this->initJobStatus('instagram_story', 'Instagram story: ' . $event->name, $event, $userId);
    }

    public function handle(InstagramEventPoster $poster): void
    {
        $this->markRunning();

        $mediaId = $poster->postStory($this->event, $this->userId);

        $this->markSucceeded(
            'Successfully published story to Instagram (media id: ' . $mediaId . ').',
            ['media_id' => $mediaId]
        );
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }
}
