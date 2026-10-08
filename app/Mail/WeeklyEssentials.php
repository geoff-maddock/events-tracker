<?php

namespace App\Mail;

use App\Mail\Concerns\HasUnsubscribeLink;
use App\Mail\Concerns\TracksEmailClicks;
use App\Models\User;
use App\Services\EmailPreferences;
use App\Services\Essentials;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The weekly update for a subscriber with nothing personal to list (#2102):
 * the week's essential events, and what to follow to make it theirs. Its own
 * subject line, so it doesn't read as a personal digest.
 */
class WeeklyEssentials extends Mailable
{
    use HasUnsubscribeLink;
    use Queueable;
    use SerializesModels;
    use TracksEmailClicks;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public User $user;

    public Essentials $essentials;

    public function __construct(string $url, string $site, string $admin_email, string $reply_email, User $user, Essentials $essentials)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->user = $user;
        $this->essentials = $essentials;
    }

    public function build(): WeeklyEssentials
    {
        return $this->markdown('emails.weekly-essentials-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': Essential events this week - '.Carbon::now()->format('l F jS Y'))
            ->withUnsubscribeLink();
    }

    /** @return array{oneClick: string, page: string}|null */
    protected function unsubscribeUrls(): ?array
    {
        return $this->userUnsubscribeUrls($this->user, EmailPreferences::WEEKLY);
    }

    protected function clickTrackedUser(): ?User
    {
        return $this->user;
    }
}
