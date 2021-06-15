<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $url;

    public $site;

    public $admin_email;

    public $reply_email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($url, $site, $admin_email, $reply_email)
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
             ->subject($this->site . ': Admin Mailer Test - ' . $dt->format('l F jS Y'))
             ->bcc($this->admin_email);
    }
}
