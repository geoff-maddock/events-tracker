<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InstagramPostFailure extends Mailable
{
    use Queueable;
    use SerializesModels;

    public int $eventId;
    public string $eventSlug;
    public string $eventName;
    public string $errorMessage;
    public string $site;
    public string $admin_email;
    public string $reply_email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(int $eventId, string $eventSlug, string $eventName, string $errorMessage, string $site, string $admin_email, string $reply_email)
    {
        $this->eventId = $eventId;
        $this->eventSlug = $eventSlug;
        $this->eventName = $eventName;
        $this->errorMessage = $errorMessage;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $dt = Carbon::now();

        return $this->markdown('emails.instagram-post-failure')
            ->from($this->reply_email, $this->site)
             ->subject($this->site.': Instagram Post Failure - '.$dt->format('l F jS Y'))
             ->to($this->admin_email);
    }
}
