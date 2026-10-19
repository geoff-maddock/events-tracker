<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a user's previous address when they change their email (#2321).
 */
class EmailChanged extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $url,
        public string $site,
        public string $admin_email,
        public string $reply_email,
        public User $user,
    ) {
    }

    /**
     * Build the message.
     */
    public function build(): EmailChanged
    {
        return $this->markdown('emails.email-changed-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.': Your account email was changed');
    }
}
