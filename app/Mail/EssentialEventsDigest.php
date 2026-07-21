<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class EssentialEventsDigest extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public NewsletterSubscriber $subscriber;

    public Collection $events;

    public string $unsubscribeUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $url, string $site, string $admin_email, string $reply_email, NewsletterSubscriber $subscriber, Collection $events)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->subscriber = $subscriber;
        $this->events = $events;
        $this->unsubscribeUrl = route('newsletter.unsubscribe', ['token' => $subscriber->token]);
    }

    /**
     * Get the message headers.
     *
     * One-click unsubscribe per RFC 8058 - protects deliverability.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    /**
     * Build the message.
     */
    public function build(): EssentialEventsDigest
    {
        $dt = Carbon::now();

        return $this->markdown('emails.essential-events-digest')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': Essential Events - '.$dt->format('l F jS Y'));
    }
}
