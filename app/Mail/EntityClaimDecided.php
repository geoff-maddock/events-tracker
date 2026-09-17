<?php

namespace App\Mail;

use App\Models\EntityClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a claimant whether their entity claim was approved or denied (#2148).
 */
class EntityClaimDecided extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public EntityClaim $claim)
    {
    }

    public function build(): EntityClaimDecided
    {
        $site = (string) config('app.app_name');
        $approved = $this->claim->status === EntityClaim::STATUS_APPROVED;

        return $this->markdown('emails.entity-claim-decided-markdown', [
            'site' => $site,
            'approved' => $approved,
            'entityUrl' => route('entities.show', $this->claim->entity),
            'adminEmail' => (string) config('app.admin'),
        ])
            ->from((string) config('app.noreplyemail'), $site)
            ->subject($site.': Your claim for '.$this->claim->entity->name.' was '.($approved ? 'approved' : 'not approved'));
    }
}
