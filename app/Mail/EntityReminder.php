<?php

namespace App\Mail;

use App\Models\Entity;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection as SupportCollection;

class EntityReminder extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    public string $site;

    public string $admin_email;

    public string $reply_email;

    public string $feedback_email;

    public Entity $entity;

    public Collection $upcomingEvents;

    public Collection $relatedEntities;

    public Collection $frequentVenues;

    public SupportCollection $frequentVenueEvents;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $url,
        string $site,
        string $admin_email,
        string $reply_email,
        string $feedback_email,
        Entity $entity,
        Collection $upcomingEvents,
        Collection $relatedEntities,
        Collection $frequentVenues,
        SupportCollection $frequentVenueEvents
    ) {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->feedback_email = $feedback_email;
        $this->entity = $entity;
        $this->upcomingEvents = $upcomingEvents;
        $this->relatedEntities = $relatedEntities;
        $this->frequentVenues = $frequentVenues;
        $this->frequentVenueEvents = $frequentVenueEvents;
    }

    /**
     * Build the message.
     */
    public function build(): EntityReminder
    {
        return $this->markdown('emails.entity-reminder-markdown')
            ->from($this->reply_email, $this->site)
            ->replyTo($this->feedback_email, $this->site)
            ->subject($this->site.': A friendly reminder - '.$this->entity->name);
    }
}
