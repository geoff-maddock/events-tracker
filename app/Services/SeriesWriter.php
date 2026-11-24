<?php

namespace App\Services;

use App\Models\Action;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;

/**
 * The one place a series is created or changed, for the web form and the API
 * alike (#2407). Controllers keep authorization, the request and the response;
 * what a write does to the series, its tags and its related entities is here.
 *
 * Input is the validated request data. Tag and entity lists hold ids, and tag
 * lists may also hold names of tags to create.
 */
class SeriesWriter
{
    /**
     * @param array<string, mixed>   $input
     * @param array<int, int|string> $tags
     * @param array<int, int|string> $entities
     * @param int|null               $eventLinkId an event to put in the new series; ignored unless
     *                                            the user may edit that event
     */
    public function create(User $user, array $input, array $tags = [], array $entities = [], ?int $eventLinkId = null): Series
    {
        $input = $this->withOwner($input, $user, $user->id);

        $series = new Series($input);
        $series->updated_by = $user->id;
        $series->save();

        $series->tags()->attach(Tag::resolveList($tags, $user)->modelKeys());
        // a repeated id would be the same link twice
        $series->entities()->attach(array_unique($entities));

        if ($eventLinkId !== null) {
            $this->linkEvent($series, $eventLinkId, $user);
        }

        Activity::log($series, $user, Action::CREATE);

        return $series;
    }

    /**
     * Change the series' own fields to the input, and its tags and related
     * entities to exactly the lists given.
     *
     * @param array<string, mixed>   $input
     * @param array<int, int|string> $tags
     * @param array<int, int|string> $entities
     */
    public function replace(Series $series, User $user, array $input, array $tags = [], array $entities = []): Series
    {
        return $this->patch($series, $user, $input, $tags, $entities);
    }

    /**
     * Change only what is given: fields present in the input, and the tag and
     * entity lists when they are not null.
     *
     * @param array<string, mixed>        $input
     * @param array<int, int|string>|null $tags
     * @param array<int, int|string>|null $entities
     */
    public function patch(Series $series, User $user, array $input, ?array $tags = null, ?array $entities = null): Series
    {
        $input = array_intersect_key($this->withOwner($input, $user, null), array_flip($series->getFillable()));

        if ($input !== []) {
            $series->fill($series->keepHiddenEntityRefs($input));
            $series->updated_by = $user->id;
            $series->save();
        }

        if ($tags !== null) {
            $series->tags()->sync(Tag::resolveList($tags, $user)->modelKeys());
        }

        if ($entities !== null) {
            $series->syncEntities($entities);
        }

        Activity::log($series, $user, Action::UPDATE);

        return $series;
    }

    /**
     * Only an admin may name another owner (#2165). Otherwise the owner is
     * $default, or left out of the input when there is no default (an update
     * keeps the owner it has).
     *
     * @param  array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function withOwner(array $input, User $user, ?int $default): array
    {
        if ($user->isAdmin() && !empty($input['created_by'])) {
            return $input;
        }

        unset($input['created_by']);
        if ($default !== null) {
            $input['created_by'] = $default;
        }

        return $input;
    }

    /**
     * Put an event in the new series and start the series with that event's photos.
     */
    private function linkEvent(Series $series, int $eventId, User $user): void
    {
        $event = Event::find($eventId);
        if (!$event || !($event->ownedBy($user) || $user->isAdmin())) {
            return;
        }

        $event->series_id = $series->id;
        $event->save();

        $series->photos()->attach($event->photos->pluck('id')->toArray());
    }
}
