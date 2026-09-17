<?php

namespace App\Mail;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a previous owner they no longer control an entity after an approved claim (#2148).
 */
class EntityOwnershipTransferred extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Entity $entity, public User $previousOwner)
    {
    }

    public function build(): EntityOwnershipTransferred
    {
        $site = (string) config('app.app_name');

        return $this->markdown('emails.entity-ownership-transferred-markdown', [
            'site' => $site,
            'entityUrl' => route('entities.show', $this->entity),
            'adminEmail' => (string) config('app.admin'),
        ])
            ->from((string) config('app.noreplyemail'), $site)
            ->subject($site.': Ownership of '.$this->entity->name.' has been transferred');
    }
}
