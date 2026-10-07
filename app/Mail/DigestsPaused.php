<?php

namespace App\Mail;

use App\Models\User;
use App\Services\EmailPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Sent once to a dormant user in place of their digest (#2083), with a
 * signed one-click link to turn the digests back on.
 */
class DigestsPaused extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $reply_email;

    public User $user;

    public function __construct(string $url, string $site, string $reply_email, User $user)
    {
        $this->url = $url;
        $this->site = $site;
        $this->reply_email = $reply_email;
        $this->user = $user;
    }

    public function build(): DigestsPaused
    {
        $digests = [];
        if ($this->user->profile?->setting_weekly_update === 1) {
            $digests[] = 'weekly update';
        }
        if ($this->user->profile?->setting_daily_update === 1) {
            $digests[] = 'daily reminder';
        }

        return $this->markdown('emails.digests-paused-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': We\'ve paused your email updates')
            ->with([
                'digests' => $digests === [] ? 'email updates' : implode(' and ', $digests),
                'resumeUrl' => URL::signedRoute('email.digests.resume', ['id' => $this->user->id]),
                // footer link, as on the digests themselves
                'unsubscribeUrl' => EmailPreferences::preferencesUrl($this->user),
            ]);
    }
}
