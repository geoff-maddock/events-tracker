<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserDataExportReady extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;
    public string $site;
    public string $admin_email;
    public string $reply_email;
    public ?User $user;
    public string $downloadUrl;
    public string $filename;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $url,
        string $site,
        string $admin_email,
        string $reply_email,
        ?User $user,
        string $downloadUrl,
        string $filename
    ) {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->user = $user;
        $this->downloadUrl = $downloadUrl;
        $this->filename = $filename;
    }

    /**
     * Build the message.
     */
    public function build(): UserDataExportReady
    {
        return $this->markdown('emails.user-data-export-ready')
            ->from($this->reply_email, $this->site)
            ->subject($this->site . ': Your Data Export is Ready')
            ->bcc($this->admin_email);
    }
}
