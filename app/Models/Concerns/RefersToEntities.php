<?php

namespace App\Models\Concerns;

use App\Models\Entity;

/**
 * For models with a venue, a promoter and a list of related entities (Event,
 * Series), any of which may be an unlisted entity the viewer cannot load.
 *
 * Such an entity's name still shows on the event or series, as plain text, and
 * editing by someone who cannot see it must not silently remove it.
 */
trait RefersToEntities
{
    use SyncsEntities;

    /**
     * Names already looked up for this model, by entity id.
     *
     * @var array<int|string, string|null>
     */
    private array $hiddenEntityNames = [];

    private function hiddenEntityName(int|string|null $id): ?string
    {
        if (!$id) {
            return null;
        }

        if (!array_key_exists($id, $this->hiddenEntityNames)) {
            $this->hiddenEntityNames[$id] = Entity::publicName($id);
        }

        return $this->hiddenEntityNames[$id];
    }

    /**
     * The venue's name, including when the venue itself is hidden from the viewer.
     */
    public function venueLabel(): ?string
    {
        return $this->venue->name ?? $this->hiddenEntityName($this->venue_id);
    }

    /**
     * The venue as anyone may see it: null when it is unlisted, whoever is
     * asking. For output that leaves the site (email, Discord), where the
     * reader is not the person whose request built it.
     */
    public function publicVenue(): ?Entity
    {
        $venue = $this->venue;

        return $venue instanceof Entity && $venue->isVisibleTo(null) ? $venue : null;
    }

    /**
     * The promoter's name, including when the promoter itself is hidden from the viewer.
     */
    public function promoterLabel(): ?string
    {
        return $this->promoter->name ?? $this->hiddenEntityName($this->promoter_id);
    }

    /**
     * Input for an update, with a hidden venue or promoter kept when the input
     * would clear it. The viewer never had it in their form or API response, so
     * an empty value is not a decision to remove it; choosing another replaces it.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function keepHiddenEntityRefs(array $input): array
    {
        foreach (['venue_id', 'promoter_id'] as $field) {
            $current = $this->getAttribute($field);

            if ($current && array_key_exists($field, $input) && empty($input[$field])
                && Entity::hiddenIds([$current]) !== []) {
                $input[$field] = $current;
            }
        }

        return $input;
    }
}
