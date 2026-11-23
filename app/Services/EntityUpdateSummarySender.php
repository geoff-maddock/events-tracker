<?php

namespace App\Services;

use App\Mail\EntityUpdateSummary;
use App\Models\Contact;
use App\Models\EmailOptOut;
use App\Models\Entity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails an entity's contact a summary of its page: upcoming and recent events
 * and who and where it most often plays.
 */
class EntityUpdateSummarySender
{
    public function send(Entity $entity): EntitySummaryResult
    {
        // find a contact email to send to
        /** @var ?Contact $contact */
        $contact = $entity->contacts()->whereNotNull('email')->where('email', '!=', '')->first();

        if (!$contact || !$contact->email) {
            return new EntitySummaryResult(EntitySummaryResult::NO_CONTACT);
        }

        if (EmailOptOut::isOptedOut($contact->email, EmailOptOut::LIST_ENTITY_CONTACT)) {
            return new EntitySummaryResult(EntitySummaryResult::OPTED_OUT, $contact->email);
        }

        // upcoming events; this goes to the entity's outside contact, so public events only
        $upcomingEvents = $entity->events()
            ->visible(null)
            ->with(['venue', 'eventType', 'visibility'])
            ->occurringBetween(Carbon::now())
            ->orderByNextStart(Carbon::now())
            ->limit(10)
            ->get();

        // past events (most recent first, up to 10)
        $pastEvents = $entity->events()
            ->visible(null)
            ->with(['venue'])
            ->where('events.last_start_at', '<', Carbon::now())
            ->orderBy('start_at', 'DESC')
            ->limit(10)
            ->get();

        $sent = (new BestEffortMailer())->attempt(fn () => Mail::to($contact->email, $contact->name ?? $entity->name)
            ->send(new EntityUpdateSummary(
                config('app.url'),
                config('app.app_name'),
                config('app.admin'),
                config('app.noreplyemail'),
                $entity,
                $upcomingEvents,
                $pastEvents,
                $entity->getFrequentlyPerformsWith(10),
                $entity->getFrequentlyPerformsAt(10)
            )), ['entity_id' => $entity->id]);

        if (!$sent) {
            return new EntitySummaryResult(EntitySummaryResult::FAILED, $contact->email);
        }

        Log::info('Entity update summary sent for entity '.$entity->id.' ('.$entity->name.') to '.$contact->email);

        return new EntitySummaryResult(EntitySummaryResult::SENT, $contact->email);
    }
}
