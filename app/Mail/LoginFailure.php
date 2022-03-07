<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginFailure extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public ?User $user;

    public ?int $fails;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $url, string $site, string $admin_email, string $reply_email, mixed $user, ?int $fails)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->user = $user;
        $this->fails = $fails;
    }

    /**
     * Build the message.
     */
    public function build(): LoginFailure
    {
        $dt = Carbon::now();

        return $this->markdown('emails.user-login-failed-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site.':  Login failure attempts - '.$this->user?->name.' - '.$dt->format('l F jS Y'))
            ->bcc($this->admin_email);
    }
}
