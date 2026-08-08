<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Admin notification that a Discord channel has stopped accepting posts
 * (issue #2058).
 *
 * Sent only for permanent failures — a malformed payload or a deleted webhook
 * — and throttled to at most one per target per hour by the poster. Transient
 * errors resolve themselves and are not worth an inbox.
 *
 * $webhook is the MASKED webhook, never the real URL: it is a bearer
 * credential and this message leaves the server.
 */
class DiscordPostFailure extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $targetName,
        public string $webhook,
        public string $errorMessage,
        public bool $wasDisabled,
        public string $site,
        public string $admin_email,
        public string $reply_email,
    ) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $dt = Carbon::now();

        return $this->markdown('emails.discord-post-failure')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': Discord Post Failure - '.$dt->format('l F jS Y'))
            ->to($this->admin_email);
    }
}
