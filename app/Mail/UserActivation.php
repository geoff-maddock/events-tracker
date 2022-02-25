<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserActivation extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public ?User $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $url, string $site, string $admin_email, string $reply_email, ?User $user)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build(): UserActivation
    {
        $dt = Carbon::now();

        return $this->markdown('emails.user-activation-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.':  Account activated - '.$this->user->name.' - '.$dt->format('l F jS Y'))
            ->bcc($this->admin_email);
    }
}
