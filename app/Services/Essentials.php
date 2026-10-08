<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Tag;
use Illuminate\Support\Collection;

/**
 * The weekly digest's fallback for a subscriber whose own digest is empty
 * (#2102): the week's most popular events, plus popular tags and entities
 * they could follow to get a personal digest instead.
 */
final class Essentials
{
    /**
     * @param Collection<int, Event> $events
     * @param Collection<int, Tag> $tags not already followed
     * @param Collection<int, Entity> $entities not already followed
     */
    public function __construct(
        public readonly Collection $events,
        public readonly Collection $tags,
        public readonly Collection $entities,
    ) {
    }

    /** Suggestions alone aren't worth an email; it's empty without events. */
    public function isEmpty(): bool
    {
        return $this->events->isEmpty();
    }

    /** @return Collection<int, int> */
    public function eventIds(): Collection
    {
        return $this->events->pluck('id');
    }
}
