<?php

namespace App\Models\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * For lookup records (types, statuses, roles) that other rows point at.
 * Deleting one that is in use would fail on a foreign key, cascade into
 * the rows using it, or leave them pointing at nothing, so it is refused.
 */
trait DeletesOnlyWhenUnused
{
    /**
     * Queries for the rows that refer to this record.
     *
     * @return array<int, Builder>
     */
    abstract protected function usedBy(): array;

    /**
     * The rows in $table whose $column holds this record's key.
     */
    protected function referencedIn(string $table, string $column): Builder
    {
        return DB::table($table)->where($column, $this->getKey());
    }

    public function isInUse(): bool
    {
        foreach ($this->usedBy() as $query) {
            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete the record unless something still refers to it. Returns false
     * (and deletes nothing) when it is in use.
     */
    public function deleteIfUnused(): bool
    {
        if ($this->isInUse()) {
            return false;
        }

        $this->delete();

        return true;
    }
}
