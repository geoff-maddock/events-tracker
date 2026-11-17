<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The "recommended for you" rule shared by events and series (Your Radar, the
 * recommended API and player queue, and the Recommended display type): a record
 * matches when it carries a tag or entity the user follows, or is (in) a series
 * they follow. Nothing matches for a user who follows none of those.
 *
 * Every match goes through a live table, so a follow left behind by a deleted
 * tag, entity or series matches nothing.
 */
trait RecommendedByFollows
{
    /**
     * Narrow $match to records in, or that are, one of $seriesIds.
     *
     * @param Builder<static>  $match
     * @param array<int, int> $seriesIds
     */
    abstract protected function orInFollowedSeries(Builder $match, array $seriesIds): void;

    /**
     * No date limit or ordering, so it composes.
     *
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeRecommendedFor(Builder $query, User $user): Builder
    {
        $followed = $user->recommendationFollows();

        if (null === $followed) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $match) use ($followed) {
            if ($followed['tag']) {
                $match->orWhereHas('tags', fn ($q) => $q->whereIn('tags.id', $followed['tag']));
            }
            if ($followed['entity']) {
                $match->orWhereHas('entities', fn ($q) => $q->whereIn('entities.id', $followed['entity']));
            }
            if ($followed['series']) {
                $this->orInFollowedSeries($match, $followed['series']);
            }
        });
    }
}
