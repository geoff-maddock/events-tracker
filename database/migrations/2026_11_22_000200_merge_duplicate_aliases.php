<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every save of an entity used to add a new alias row for each name typed,
     * so the same name exists many times over. For each name the earliest row is
     * kept: entities linked to a later copy are linked to it instead, and the
     * copies are deleted. Then the name is made unique so it cannot recur.
     *
     * "Same name" is whatever the column's collation calls equal, which is also
     * what the unique index will enforce.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // copy => keeper
            $copies = DB::table('aliases as a')
                ->join(DB::raw('(SELECT name, MIN(id) AS keep_id FROM aliases WHERE name IS NOT NULL GROUP BY name) k'), 'k.name', '=', 'a.name')
                ->whereColumn('a.id', '<>', 'k.keep_id')
                ->pluck('k.keep_id', 'a.id');

            foreach ($copies as $copyId => $keepId) {
                $linked = DB::table('alias_entity')->where('alias_id', $keepId)->pluck('entity_id')->all();

                // move the links the keeper does not have yet; the rest go with the copy
                DB::table('alias_entity')->where('alias_id', $copyId)->whereNotIn('entity_id', $linked)->update(['alias_id' => $keepId]);
                DB::table('alias_entity')->where('alias_id', $copyId)->delete();
                DB::table('aliases')->where('id', $copyId)->delete();
            }

            // an entity linked twice to one alias (two copies it held, now one)
            $doubles = DB::table('alias_entity')->select('alias_id', 'entity_id')
                ->groupBy('alias_id', 'entity_id')->havingRaw('COUNT(*) > 1')->get();
            foreach ($doubles as $double) {
                DB::table('alias_entity')->where('alias_id', $double->alias_id)->where('entity_id', $double->entity_id)->delete();
                DB::table('alias_entity')->insert(['alias_id' => $double->alias_id, 'entity_id' => $double->entity_id]);
            }
        });

        if (!Schema::hasIndex('aliases', ['name'], 'unique')) {
            Schema::table('aliases', function (Blueprint $table) {
                $table->unique('name', 'aliases_name_unique');
            });
        }
    }

    /**
     * The merged copies are not restored.
     */
    public function down(): void
    {
        if (Schema::hasIndex('aliases', ['name'], 'unique')) {
            Schema::table('aliases', function (Blueprint $table) {
                $table->dropUnique('aliases_name_unique');
            });
        }
    }
};
