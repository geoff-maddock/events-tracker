<?php

namespace App\Filters;

use App\Models\DiscordTarget;
use App\Models\DiscordTargetCriterion;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filters for the Discord targets admin list (issue #2058).
 */
class DiscordTargetFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (! isset($value) || '' === $value) {
            return $this->builder;
        }

        return $this->builder->where('name', 'like', '%'.$value.'%');
    }

    /**
     * Accepts '1'/'0' from a select, so an explicit "disabled" filter works.
     */
    public function enabled(string|int|bool|null $value = null): Builder
    {
        if (! isset($value) || '' === $value) {
            return $this->builder;
        }

        return $this->builder->where('is_enabled', (bool) $value);
    }

    /**
     * Targets opted into a posting mode: create, reminder, digest or manual.
     */
    public function mode(?string $value = null): Builder
    {
        if (! isset($value) || '' === $value) {
            return $this->builder;
        }

        $column = DiscordTarget::MODE_COLUMNS[$value] ?? null;

        if (null === $column) {
            return $this->builder;
        }

        return $this->builder->where($column, true);
    }

    /**
     * Targets carrying a criterion for a given tag id.
     */
    public function tag(string|int|null $value = null): Builder
    {
        if (! isset($value) || '' === $value) {
            return $this->builder;
        }

        return $this->builder->whereHas('criteria', function (Builder $query) use ($value): void {
            $query->where('criteria_type', DiscordTargetCriterion::TYPE_TAG)
                ->where('criteria_id', (int) $value);
        });
    }
}
