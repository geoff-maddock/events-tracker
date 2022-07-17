<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twitter\TwitterChannel;
use NotificationChannels\Twitter\TwitterStatusUpdate;
use Storage;

class EventPublished extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return [TwitterChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toArray($notifiable): array
    {
        return [];
    }

    /**
     * @param Notifiable $notifiable
     */

    /** @phpstan-ignore-next-line */
    public function toTwitter($notifiable): TwitterStatusUpdate
    {
        if ($photo = $notifiable->getPrimaryPhoto()) {
            // copy the file to local
            $temp = Storage::disk('external')->get($photo->getTwitterPath());
            $public = Storage::disk('public');
            $public->put('./photos/temp/'.$photo->name, $temp);

            return (new TwitterStatusUpdate($notifiable->getBriefFormat()))->withImage('storage/photos/temp/'.$photo->name);
        }

        return new TwitterStatusUpdate($notifiable->getBriefFormat());
    }
}
