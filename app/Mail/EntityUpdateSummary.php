<?php

namespace App\Mail;

use App\Models\Entity;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EntityUpdateSummary extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public Entity $entity;

    public Collection $upcomingEvents;

    public Collection $pastEvents;

    public Collection $frequentlyPerformsWith;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        string $url,
        string $site,
        string $admin_email,
        string $reply_email,
        Entity $entity,
        Collection $upcomingEvents,
        Collection $pastEvents,
        Collection $frequentlyPerformsWith
    ) {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->entity = $entity;
        $this->upcomingEvents = $upcomingEvents;
        $this->pastEvents = $pastEvents;
        $this->frequentlyPerformsWith = $frequentlyPerformsWith;
    }

    /**
     * Build the message.
     */
    public function build(): EntityUpdateSummary
    {
        $dt = Carbon::now();

        return $this->markdown('emails.entity-update-summary-markdown')
            ->from($this->reply_email, $this->site)
            ->subject($this->site . ': Update Summary for ' . $this->entity->name . ' - ' . $dt->format('F Y'));
    }
}
