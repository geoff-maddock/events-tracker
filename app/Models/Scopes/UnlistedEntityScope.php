<?php

namespace App\Models\Scopes;

use App\Models\EntityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Entities with the Unlisted status exist for admins only.
 *
 * Applied to every Entity query, relation and route binding, so a list, page,
 * feed or API response cannot include one by forgetting to filter. Anything
 * without an admin behind it (guests, members, queue workers, the scheduler)
 * gets the filtered view. Entity::withUnlisted() opts out, for code that has a
 * reason to look at every row.
 */
class UnlistedEntityScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (self::viewerSeesUnlisted()) {
            return;
        }

        $builder->where($model->qualifyColumn('entity_status_id'), '<>', EntityStatus::UNLISTED);
    }

    /**
     * A global scope for a model that hangs off entities (locations, links,
     * contacts): rows that belong only to unlisted entities are hidden with them.
     *
     * @param string      $column the column holding the entity id, or this model's key when $pivot is given
     * @param string|null $pivot  "table.column" of a pivot whose entity_id links the model to entities
     */
    public static function following(string $column, ?string $pivot = null): \Closure
    {
        return function (Builder $builder) use ($column, $pivot): void {
            if (self::viewerSeesUnlisted()) {
                return;
            }

            $entities = fn (string $operator) => DB::table('entities')->select('id')
                ->where('entity_status_id', $operator, EntityStatus::UNLISTED);
            $column = $builder->qualifyColumn($column);

            if (null === $pivot) {
                $builder->whereNotIn($column, $entities('='));

                return;
            }

            // many-to-many: a row shared with a listed entity stays visible through that one
            [$table, $key] = explode('.', $pivot);
            $attachedTo = fn (string $operator) => DB::table($table)->select($key)->whereIn('entity_id', $entities($operator));

            $builder->where(fn (Builder $query) => $query
                ->whereNotIn($column, $attachedTo('='))
                ->orWhereIn($column, $attachedTo('<>')));
        };
    }

    /**
     * Whether the current request is made by an admin.
     */
    public static function viewerSeesUnlisted(): bool
    {
        return (bool) Auth::user()?->isAdmin();
    }
}
