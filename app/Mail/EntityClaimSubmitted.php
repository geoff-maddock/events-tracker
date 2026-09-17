<?php

namespace App\Mail;

use App\Models\EntityClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the admins a new entity claim is waiting for review (#2148).
 */
class EntityClaimSubmitted extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public EntityClaim $claim)
    {
    }

    public function build(): EntityClaimSubmitted
    {
        $site = (string) config('app.app_name');

        return $this->markdown('emails.entity-claim-submitted-markdown', [
            'site' => $site,
            'reviewUrl' => route('entity-claims.index'),
            'entityUrl' => route('entities.show', $this->claim->entity),
        ])
            ->from((string) config('app.noreplyemail'), $site)
            ->subject($site.': Claim request for '.$this->claim->entity->name.' from '.$this->claim->user->name);
    }
}
