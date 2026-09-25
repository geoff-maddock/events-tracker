<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Post;
use App\Models\Thread;
use App\Services\FollowerNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Emails the followers of a new event, thread or post in the background, so the
 * request that created it doesn't wait on one SMTP send per follower (#2170).
 */
class NotifyFollowers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // a retry after a partial run would email the same followers twice; the
    // mailer already swallows per-recipient failures, so one attempt is enough
    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public Event|Thread|Post $subject)
    {
        // run after the surrounding transaction commits, so tags/entities are in place
        $this->afterCommit();
    }

    public function handle(FollowerNotifier $notifier): void
    {
        match (true) {
            $this->subject instanceof Event => $notifier->event($this->subject),
            $this->subject instanceof Thread => $notifier->thread($this->subject),
            $this->subject instanceof Post => $notifier->post($this->subject),
        };
    }
}
