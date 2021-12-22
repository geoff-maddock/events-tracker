<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FollowingPostUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $url;

    public $site;

    public $admin_email;

    public $reply_email;

    public $user;

    public $thread;

    public $post;

    public $tag;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($url, $site, $admin_email, $reply_email, $user, $thread, $post, $tag = null)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->user = $user;
        $this->thread = $thread;
        $this->post = $post;
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

        return $this->markdown('emails.following-post-update-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site . ': New post by ' . $this->post->user->name . ' in thread "' . $this->thread->name . '"')
            ->bcc($this->admin_email);
    }
}
