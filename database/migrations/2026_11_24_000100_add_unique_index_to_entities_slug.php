<?php

use App\Services\OptionLists;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Entity pages are found by slug, but the slug index was not unique (#2413).
 * Where a slug is held by more than one entity, the one the page already
 * showed keeps it (the earliest that is not unlisted) and the others get a
 * numbered slug. Then the plain index is replaced by a unique one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('entities', ['slug'], 'unique')) {
            return;
        }

        if ($this->renameDuplicates()) {
            // the cached filter options are keyed by slug
            OptionLists::forgetEntities();
        }

        Schema::table('entities', function (Blueprint $table) {
            // by column: the plain index may have been added under another name
            if (Schema::hasIndex('entities', ['slug'])) {
                $table->dropIndex(collect(Schema::getIndexes('entities'))
                    ->first(fn (array $index) => $index['columns'] === ['slug'])['name']);
            }

            $table->unique('slug');
        });
    }

    /**
     * The renamed slugs are not restored.
     */
    public function down(): void
    {
        // by name: a unique index that was already there under another name is not this migration's
        if (!Schema::hasIndex('entities', 'entities_slug_unique')) {
            return;
        }

        Schema::table('entities', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->index('slug');
        });
    }

    /**
     * Raw queries on purpose: the Entity model hides unlisted entities, and
     * they hold their slugs too. An unlisted entity gives way, though: for
     * everyone but an admin the page showed the other one.
     *
     * @return bool whether a slug was changed
     */
    private function renameDuplicates(): bool
    {
        $unlisted = DB::table('entity_statuses')->where('name', 'Unlisted')->value('id') ?? 0;

        $repeated = DB::table('entities')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug');

        foreach ($repeated as $slug) {
            $ids = DB::table('entities')
                ->where('slug', $slug)
                ->orderByRaw('entity_status_id <=> ?', [$unlisted])
                ->orderBy('id')
                ->pluck('id');
            $n = 2;

            // the first keeps the slug
            foreach ($ids->skip(1) as $id) {
                while (DB::table('entities')->where('slug', $slug.'-'.$n)->exists()) {
                    ++$n;
                }

                DB::table('entities')->where('id', $id)->update(['slug' => $slug.'-'.$n]);
            }
        }

        return $repeated->isNotEmpty();
    }
};
