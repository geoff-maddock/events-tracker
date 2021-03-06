<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $url;

    public $site;

    public $admin_email;

    public $reply_email;

    public $user;

    public $events;

    public $seriesList;

    public $interests;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($url, $site, $admin_email, $reply_email, $user, $events, $seriesList, $interests)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->user = $user;
        $this->events = $events;
        $this->seriesList = $seriesList;
        $this->interests = $interests;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $dt = Carbon::now();

        return $this->markdown('emails.user-update-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site . ': Site updates for ' . $this->user->name . ' - ' . $dt->format('l F jS Y'))
            ->bcc($this->admin_email);
    }
}
