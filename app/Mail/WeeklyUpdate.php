<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyUpdate extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public ?User $user;

    public Collection $events;

    public array $seriesList;

    public array $interests;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $url, string $site, string $admin_email, string $reply_email, ?User $user, Collection $events, array $seriesList, array $interests)
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
     */
    public function build(): WeeklyUpdate
    {
        $dt = Carbon::now();

        return $this->markdown('emails.weekly-update-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': Weekly Update - '.$dt->format('l F jS Y'))
            ->bcc($this->admin_email);
    }
}
