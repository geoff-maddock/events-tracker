<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * For lookup records (types, statuses, roles) that other rows point at.
 * Deleting one that is in use would fail on a foreign key, cascade into
 * the rows using it, or leave them pointing at nothing, so it is refused.
 */
trait DeletesOnlyWhenUnused
{
    /**
     * The table => column pairs that refer to this record.
     *
     * @return array<string, string>
     */
    abstract protected function usedBy(): array;

    public function isInUse(): bool
    {
        foreach ($this->usedBy() as $table => $column) {
            if (DB::table($table)->where($column, $this->getKey())->exists()) {
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
