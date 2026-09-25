<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmSubscription extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public NewsletterSubscriber $subscriber;

    public string $confirmUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $url, string $site, string $admin_email, string $reply_email, NewsletterSubscriber $subscriber)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->subscriber = $subscriber;
        $this->confirmUrl = route('newsletter.confirm', ['token' => $subscriber->token]);
    }

    /**
     * Build the message.
     */
    public function build(): NewsletterConfirmSubscription
    {
        return $this->markdown('emails.newsletter-confirm')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': Confirm your Essential Events subscription');
    }
}
