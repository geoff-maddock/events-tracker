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
 * Queued job that posts an event to the Instagram feed, as a single photo or
 * a carousel. Progress is tracked through the associated JobStatus row.
 */
class PostEventToInstagram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksJobStatus;

    /** Instagram uploads poll for several minutes; allow plenty of headroom. */
    public int $timeout = 600;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 180];

    public function __construct(
        public Event $event,
        public bool $carousel,
        public ?int $userId
    ) {
        $label = ($carousel ? 'Carousel post' : 'Instagram post') . ': ' . $event->name;
        $this->initJobStatus('instagram_post', $label, $event, $userId);
    }

    public function handle(InstagramEventPoster $poster): void
    {
        $this->markRunning();

        $mediaId = $this->carousel
            ? $poster->postCarousel($this->event, $this->userId)
            : $poster->postSingle($this->event, $this->userId);

        $this->markSucceeded(
            'Successfully published to Instagram (media id: ' . $mediaId . ').',
            ['media_id' => $mediaId]
        );
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }
}
