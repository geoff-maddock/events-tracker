<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\Visibility;
use Illuminate\Database\Eloquent\Builder;

/**
 * The visibility rule events, series and threads share (#2408), for a model
 * with `visibility_id` and `created_by` columns:
 *
 * - public: everyone;
 * - guarded: anyone signed in;
 * - proposal and private: their creator;
 * - cancelled: in no list. Its page stays open (shared links keep working)
 *   unless the model says otherwise in opensWhenCancelled().
 *
 * visible() and visibleTo() are for lists; isVisibleTo() is the same rule for
 * one record, where an admin sees everything.
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait HasVisibility
{
    /**
     * What $user may see in a list.
     *
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeVisible(Builder $query, ?User $user): Builder
    {
        return $query->where(static::visibleTo($user));
    }

    /**
     * Only what everyone may see: for output that leaves the site or is built
     * for no one in particular (sitemap, social posts, counts, caches).
     *
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('visibility_id'), '=', Visibility::VISIBILITY_PUBLIC);
    }

    /**
     * The where-group behind visible(), on this model's table, so a query that
     * joins the table (e.g. photos to events) can apply the same rule.
     *
     * @return \Closure(\Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Query\Builder): void
     */
    public static function visibleTo(?User $user): \Closure
    {
        $table = (new static())->getTable();

        return function ($query) use ($user, $table) {
            $query->where($table.'.visibility_id', '=', Visibility::VISIBILITY_PUBLIC);
            // signed in: their own proposals and private records, and guarded ones.
            // Never compare created_by to null: `= null` compiles to IS NULL, which
            // showed guests every private record whose creator had been deleted.
            if ($user) {
                $query->orWhere(function ($own) use ($user, $table) {
                    $own->whereIn($table.'.visibility_id', [Visibility::VISIBILITY_PROPOSAL, Visibility::VISIBILITY_PRIVATE])
                        ->where($table.'.created_by', '=', $user->id);
                })->orWhere($table.'.visibility_id', '=', Visibility::VISIBILITY_GUARDED);
            }
        };
    }

    /**
     * Whether $user may open this record's page or API record (#2164).
     */
    public function isVisibleTo(?User $user): bool
    {
        if ($user?->isAdmin()) {
            return true;
        }

        return match ((int) $this->visibility_id) {
            Visibility::VISIBILITY_PUBLIC => true,
            Visibility::VISIBILITY_PROPOSAL, Visibility::VISIBILITY_PRIVATE => $user !== null && (int) $this->created_by === $user->id,
            Visibility::VISIBILITY_GUARDED => $user !== null,
            default => $this->opensWhenCancelled(),
        };
    }

    /**
     * Whether a cancelled record's own page still opens for everyone.
     */
    protected function opensWhenCancelled(): bool
    {
        return true;
    }
}
