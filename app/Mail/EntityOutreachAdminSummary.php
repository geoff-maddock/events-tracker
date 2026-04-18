<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EntityOutreachAdminSummary extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public string $feedback_email;

    public Collection $instagramEntities;

    public int $emailedCount;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $url,
        string $site,
        string $admin_email,
        string $reply_email,
        string $feedback_email,
        Collection $instagramEntities,
        int $emailedCount
    ) {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->feedback_email = $feedback_email;
        $this->instagramEntities = $instagramEntities;
        $this->emailedCount = $emailedCount;
    }

    /**
     * Build the message.
     */
    public function build(): EntityOutreachAdminSummary
    {
        $dt = Carbon::now();

        return $this->markdown('emails.entity-outreach-admin-markdown')
            ->from($this->reply_email, $this->site)
            ->replyTo($this->feedback_email, $this->site)
            ->subject($this->site.': Entity Outreach Summary - '.$dt->format('F Y'));
    }
}
