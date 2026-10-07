<?php

namespace App\Mail;

use App\Mail\Concerns\HasUnsubscribeLink;
use App\Mail\Concerns\TracksEmailClicks;
use App\Models\User;
use App\Services\EmailPreferences;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class UserUpdate extends Mailable
{
    use HasUnsubscribeLink;
    use Queueable;
    use SerializesModels;
    use TracksEmailClicks;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public ?User $user;

    /** @var Collection<int, \App\Models\Event> */
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
     *
     * @return $this
     */
    public function build(): UserUpdate
    {
        $dt = Carbon::now();

        return $this->markdown('emails.user-update-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': Site updates for '.$this->user?->name.' - '.$dt->format('l F jS Y'))
            ->withUnsubscribeLink();
    }

    /** @return array{oneClick: string, page: string}|null */
    protected function unsubscribeUrls(): ?array
    {
        return $this->userUnsubscribeUrls($this->user, EmailPreferences::DAILY);
    }

    protected function clickTrackedUser(): ?User
    {
        return $this->user;
    }
}
