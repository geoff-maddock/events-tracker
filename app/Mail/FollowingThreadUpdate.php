<?php

namespace App\Mail;

use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FollowingThreadUpdate extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public ?User $user;

    public ?Thread $thread;

    public ?Tag $tag;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $url, string $site, string $admin_email, string $reply_email, ?User $user, ?Thread $thread, ?Tag $tag = null)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->user = $user;
        $this->thread = $thread;
        $this->tag = $tag;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $dt = Carbon::now();

        return $this->markdown('emails.following-thread-update-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': '.$this->tag?->name.' :: '.$this->thread?->created_at->format('D F jS').' '.$this->thread?->name)
            ->bcc($this->admin_email);
    }
}
