<?php

namespace App\Services;

use App\Events\EventCreated;
use App\Events\EventUpdated;
use App\Jobs\NotifyFollowers;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * The one place an event is created or changed, for the web form and the API
 * alike (#2407). Controllers keep authorization, the request and the response;
 * what a write does to the event, its tags, related entities and showings, and
 * what it announces, is here.
 *
 * Input is the validated request data. Tag and entity lists hold ids, and tag
 * lists may also hold names of tags to create. Showings are rows of
 * start_at / end_at / door_at.
 */
class EventWriter
{
    /**
     * @param array<string, mixed>             $input
     * @param array<int, int|string>           $tags
     * @param array<int, int|string>           $entities
     * @param array<int, array<string, mixed>> $showings
     * @param (callable(Event): void)|null     $beforeAnnouncing runs once the event is stored and before
     *                                                           anyone is told about it (to attach its photo)
     */
    public function create(User $user, array $input, array $tags = [], array $entities = [], array $showings = [], ?callable $beforeAnnouncing = null): Event
    {
        $input['slug'] = Str::slug($input['slug'] ?? '-');
        // a new event belongs to whoever made it
        $input['created_by'] = $user->id;

        $event = new Event($input);
        $event->updated_by = $user->id;
        $event->save();

        $event->tags()->attach(Tag::resolveList($tags, $user)->modelKeys());
        $event->entities()->attach($entities);
        $this->relateVenueAndPromoter($event);
        $event->syncShowings($showings);

        if ($beforeAnnouncing !== null) {
            $beforeAnnouncing($event);
        }

        Activity::log($event, $user, Action::CREATE);
        EventCreated::dispatch($event);

        // followers of its tags and entities hear about an upcoming event that has a photo
        if ($event->getPrimaryPhoto() !== null && $event->start_at >= Carbon::now()) {
            NotifyFollowers::dispatch($event);
        }

        return $event;
    }

    /**
     * Change the event's own fields to the input, and its tags, related
     * entities and showings to exactly the lists given.
     *
     * @param array<string, mixed>             $input
     * @param array<int, int|string>           $tags
     * @param array<int, int|string>           $entities
     * @param array<int, array<string, mixed>> $showings
     */
    public function replace(Event $event, User $user, array $input, array $tags = [], array $entities = [], array $showings = []): Event
    {
        return $this->patch($event, $user, $input, $tags, $entities, $showings);
    }

    /**
     * Change only what is given: fields present in the input, and the tag,
     * entity and showing lists when they are not null.
     *
     * @param array<string, mixed>                  $input
     * @param array<int, int|string>|null           $tags
     * @param array<int, int|string>|null           $entities
     * @param array<int, array<string, mixed>>|null $showings
     */
    public function patch(Event $event, User $user, array $input, ?array $tags = null, ?array $entities = null, ?array $showings = null): Event
    {
        // An owner may hand the event to someone else, but an empty owner must
        // not reach the NOT NULL column (EVENTREPO-VM).
        if (empty($input['created_by'])) {
            unset($input['created_by']);
        }
        $input = array_intersect_key($input, array_flip($event->getFillable()));

        if ($input !== []) {
            $event->fill($event->keepHiddenEntityRefs($input));
            $event->updated_by = $user->id;
            $event->save();
        }

        if ($showings !== null) {
            $event->syncShowings($showings);
        } elseif ($event->wasChanged('start_at')) {
            // a moved first date may have overtaken some of the stored showings
            $event->syncShowings($event->showings->map->only(['start_at', 'end_at', 'door_at'])->all());
        }

        if ($tags !== null) {
            $event->tags()->sync(Tag::resolveList($tags, $user)->modelKeys());
        }

        if ($entities !== null) {
            $event->syncEntities($entities);
        }
        $this->relateVenueAndPromoter($event);

        Activity::log($event, $user, Action::UPDATE);
        EventUpdated::dispatch($event);

        return $event;
    }

    /**
     * An event's venue and promoter are always among its related entities.
     */
    private function relateVenueAndPromoter(Event $event): void
    {
        $ids = array_filter([$event->venue_id, $event->promoter_id]);

        if ($ids !== []) {
            $event->entities()->syncWithoutDetaching($ids);
        }
    }
}
