<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Series;
use Illuminate\Support\Collection;

/**
 * What one user's daily or weekly digest email lists (see DigestBuilder).
 */
final class Digest
{
    /**
     * @param Collection<int, Event> $attending events the user is going to
     * @param array<int, Series> $series followed series to mention
     * @param array<string, array<int, Event>> $interests events from followed entities and tags, by their name
     */
    public function __construct(
        public readonly Collection $attending,
        public readonly array $series,
        public readonly array $interests,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->attending->isEmpty() && $this->series === [] && $this->interests === [];
    }

    /**
     * Ids of every event the digest lists.
     *
     * @return Collection<int, int>
     */
    public function eventIds(): Collection
    {
        return $this->attending->pluck('id')->merge(collect($this->interests)->flatten(1)->pluck('id'));
    }
}
