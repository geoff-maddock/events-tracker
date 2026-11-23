<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Builder;

/**
 * Finds existing entities whose name or alias matches a name being entered, so
 * the event quick-add modal and the entity form can warn about likely duplicates.
 */
class EntityDuplicateFinder
{
    /**
     * The five closest matches, best first.
     *
     * @param ?int $typeId    restrict matches to one entity type: the same name
     *                        legitimately exists across types (a band and the venue
     *                        named after it)
     * @param ?int $excludeId the entity being edited, which must not match itself
     *
     * @return list<array{id: int, name: string, slug: string, entity_type: string|null, alias: string|null}>
     */
    public function matches(string $name, ?int $typeId = null, ?int $excludeId = null): array
    {
        $candidates = Entity::where(function (Builder $query) use ($name) {
            $query->where('name', 'like', '%'.$name.'%')
                ->orWhereRaw('SOUNDEX(name) = SOUNDEX(?)', [$name])
                ->orWhereHas('aliases', function ($q) use ($name) {
                    $q->where('name', $name);
                });
        })
            ->when($typeId, fn (Builder $query) => $query->where('entity_type_id', $typeId))
            ->when($excludeId, fn (Builder $query) => $query->where('id', '!=', $excludeId))
            ->with(['entityType', 'aliases'])
            ->limit(25)
            ->get();

        return $candidates
            ->map(function (Entity $entity) use ($name) {
                $matchedAlias = $entity->aliases->first(
                    fn ($alias) => 0 === strcasecmp($alias->name, $name)
                );
                similar_text(mb_strtolower($name), mb_strtolower($entity->name), $percent);

                return [
                    'entity' => $entity,
                    'alias' => $matchedAlias?->name,
                    'score' => $matchedAlias ? 100.0 : $percent,
                    'keep' => $matchedAlias
                        || $percent >= 60
                        || false !== mb_stripos($entity->name, $name)
                        || false !== mb_stripos($name, $entity->name),
                ];
            })
            ->filter(fn (array $match) => $match['keep'])
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->map(fn (array $match) => [
                'id' => $match['entity']->id,
                'name' => $match['entity']->name,
                'slug' => $match['entity']->slug,
                'entity_type' => $match['entity']->entityType?->name,
                // Set when the input matched one of the entity's aliases rather
                // than its name, so the UI can explain why it surfaced.
                'alias' => $match['alias'],
            ])
            ->all();
    }
}
