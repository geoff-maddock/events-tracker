<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminMailer extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $url, string $site, string $admin_email, string $reply_email)
    {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $dt = Carbon::now();

        // html - extends email layout
        // return $this->view('emails.admin-test')
        // ->from($this->reply_email, $this->site)
        //  ->subject($this->site . ': Admin Mailer Test - ' . $dt->format('l F jS Y'))
        //  ->bcc($this->admin_email);

        // markdown
        return $this->markdown('emails.admin-test-markdown')
            ->from($this->reply_email, $this->site)
             ->subject($this->site.': Admin Mailer Test - '.$dt->format('l F jS Y'))
             ->bcc($this->admin_email);
    }
}
