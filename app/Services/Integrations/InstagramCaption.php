<?php

namespace App\Services\Integrations;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The caption text of an Instagram post about an event or an entity: the
 * details, then @handles and hashtags, then links.
 */
class InstagramCaption
{
    /** Instagram's caption limit is 2,200; the optional parts of an event caption stop short of it. */
    private const EVENT_MAX_LENGTH = 2100;

    /** An entity's own link is added only while the caption is still this short. */
    private const ENTITY_LINK_BELOW_LENGTH = 258;

    /** How many upcoming events an entity caption lists. */
    private const ENTITY_UPCOMING_EVENTS = 9;

    /**
     * $showingsListed caps how many of a short run's dates are written out.
     */
    public static function forEvent(Event $event, int $showingsListed = 20): string
    {
        // a short run leads with its next date (#2375)
        $start = $event->display_start_at ?? $event->start_at;

        // the date and name are always included
        $caption = $start->format('l F jS Y').' | '.$event->name;

        if (!empty($event->series_id)) {
            $caption .= ' '.$event->series->name.' series';
        }

        $caption .= ' '.$event->eventType->name;

        if ($event->venueLabel()) {
            $caption .= ' at '.$event->venueLabel();
        }

        $caption .= ' at '.$start->format('gA');

        // the door price ("Free" for 0)
        if ($price = $event->doorPriceLabel()) {
            $caption .= ' '.$price;
        }

        // every date a short run has left, in the one post (#2375)
        if ($dates = $event->showtimesSummary($showingsListed)) {
            $caption .= "\nShowings: ".$dates."\n";
        }

        $caption .= ' '.$event->short;

        foreach ($event->entities as $entity) {
            $caption .= ' '.self::handleOrHashtag($entity);
        }

        $caption .= self::hashtags($event->tags)."\n";

        $caption .= ' '.rtrim(config('app.url'), '/').'/events/'.$event->slug;

        // the ticket link and the description go in only when they fit
        if ($event->primary_link && strlen($caption) + strlen($event->primary_link) < self::EVENT_MAX_LENGTH) {
            $caption .= ' '.$event->primary_link;
        }

        if ($event->description && strlen($caption) + strlen($event->description) < self::EVENT_MAX_LENGTH) {
            $caption .= "\n\n-----\nMore info\n ".$event->description;
        }

        return $caption;
    }

    public static function forEntity(Entity $entity): string
    {
        $caption = "\n".$entity->name."\n";

        $caption .= $entity->entityType->name.' - '.$entity->getRoleString()."\n";

        $location = $entity->getPrimaryLocation();
        if ($location) {
            if (!empty($location->address_one)) {
                $caption .= $location->address_one;
            }

            foreach (['address_two', 'neighborhood', 'city', 'state', 'country'] as $part) {
                if (!empty($location->{$part})) {
                    $caption .= ' '.$location->{$part};
                }
            }

            $caption .= "\n\n";
        }

        if (!empty($entity->description)) {
            $caption .= $entity->description."\n";
        }

        $caption .= ' '.self::handleOrHashtag($entity);

        $caption .= self::hashtags($entity->tags)."\n";

        $caption .= ' '.rtrim(config('app.url'), '/').'/entities/'.$entity->slug."\n";

        $primaryLink = $entity->primaryLink();
        if ($primaryLink && strlen($caption) < self::ENTITY_LINK_BELOW_LENGTH) {
            $caption .= ' '.$primaryLink->url;
        }

        $events = $entity->futureEvents(self::ENTITY_UPCOMING_EVENTS)->items();
        if (count($events) > 0) {
            $caption .= "\n\nUpcoming Events:";
            foreach ($events as $event) {
                $caption .= "\n\n".$event->start_at->format('m/d').' '.$event->name.' at '.($event->venueLabel() ?? 'No venue specified');
            }
        }

        return $caption;
    }

    /**
     * An entity's Instagram @handle, or a hashtag of its slug when it has none.
     */
    private static function handleOrHashtag(Entity $entity): string
    {
        return !empty($entity->instagram_username)
            ? '@'.$entity->instagram_username
            : '#'.Str::studly($entity->slug);
    }

    /**
     * The tags as hashtags, the site's default hashtag, and the extra handles
     * and tags from config (app.instagram_additional_handles, comma separated).
     * Each one is led by a space.
     *
     * @param Collection<int, Tag> $tags
     */
    private static function hashtags(Collection $tags): string
    {
        $text = '';

        foreach ($tags as $tag) {
            $text .= ' #'.Str::studly($tag->name);
        }

        $text .= ' #'.config('app.default_hashtag');

        foreach (explode(',', (string) config('app.instagram_additional_handles')) as $handle) {
            $handle = trim($handle);
            if (!empty($handle)) {
                $text .= ' '.$handle;
            }
        }

        return $text;
    }
}
