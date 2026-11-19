<?php

namespace App\Models\Concerns;

use App\Models\Entity;

/**
 * For models with a list of related entities (events, series, threads, posts,
 * blogs), some of which may be unlisted entities the editor cannot see.
 */
trait SyncsEntities
{
    /**
     * Sync the related entities, keeping any attached ones the viewer cannot
     * see: they were never in the editor's form or API response, so leaving
     * them out is not a decision to remove them.
     *
     * @param array<int, int|string>|null $ids
     */
    public function syncEntities(?array $ids): void
    {
        $ids = array_map('intval', $ids ?? []);
        $attached = $this->entities()->withoutGlobalScopes()->pluck('entities.id')->all();

        $this->entities()->sync(array_values(array_unique([...$ids, ...Entity::hiddenIds($attached)])));
    }
}
