<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FollowingUpdate extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public ?User $user;

    public ?Event $event;

    public mixed $tag;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $url, string $site, string $admin_email, string $reply_email, ?User $user, ?Event $event, mixed $tag = null)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->user = $user;
        $this->event = $event;
        $this->tag = $tag;
    }

    /**
     * Build the message.
     */
    public function build(): FollowingUpdate
    {
        $dt = Carbon::now();

        return $this->markdown('emails.following-update-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': '.$this->tag?->name.' :: '.$this->event?->start_at->format('D F jS').' '.$this->event?->name)
            ->bcc($this->admin_email);
    }
}
