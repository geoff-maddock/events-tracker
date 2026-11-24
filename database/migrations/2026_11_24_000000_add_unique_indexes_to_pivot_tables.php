<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The link tables had a plain index on each column and nothing to stop the
 * same pair being stored twice (#2413). Extra rows go first (the earliest is
 * kept), then each table gets a unique index on its pair.
 */
return new class extends Migration
{
    /**
     * table => its pair of columns.
     *
     * @var array<string, array{string, string}>
     */
    private array $pivots = [
        'event_tag' => ['event_id', 'tag_id'],
        'entity_event' => ['event_id', 'entity_id'],
        'entity_tag' => ['entity_id', 'tag_id'],
        'entity_role' => ['entity_id', 'role_id'],
        'entity_series' => ['series_id', 'entity_id'],
        'series_tag' => ['series_id', 'tag_id'],
        'tag_thread' => ['thread_id', 'tag_id'],
        'entity_thread' => ['thread_id', 'entity_id'],
        'series_thread' => ['thread_id', 'series_id'],
        'blog_tag' => ['blog_id', 'tag_id'],
        'blog_entity' => ['blog_id', 'entity_id'],
        'post_tag' => ['post_id', 'tag_id'],
        'entity_post' => ['post_id', 'entity_id'],
        'entity_link' => ['entity_id', 'link_id'],
        'link_user' => ['user_id', 'link_id'],
        'contact_entity' => ['entity_id', 'contact_id'],
        'group_user' => ['group_id', 'user_id'],
        'group_permission' => ['group_id', 'permission_id'],
        'event_photo' => ['event_id', 'photo_id'],
        'entity_photo' => ['entity_id', 'photo_id'],
        'photo_series' => ['series_id', 'photo_id'],
        'photo_user' => ['user_id', 'photo_id'],
        'blog_photo' => ['blog_id', 'photo_id'],
    ];

    public function up(): void
    {
        foreach ($this->pivots as $table => $pair) {
            if (!Schema::hasTable($table) || Schema::hasIndex($table, $pair, 'unique')) {
                continue;
            }

            $this->removeDuplicates($table, $pair);

            Schema::table($table, function (Blueprint $blueprint) use ($pair) {
                $blueprint->unique($pair);
            });
        }
    }

    /**
     * The removed duplicates are not restored.
     */
    public function down(): void
    {
        foreach ($this->pivots as $table => $pair) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $pair, 'unique')) {
                Schema::table($table, function (Blueprint $blueprint) use ($pair) {
                    $blueprint->dropUnique($pair);
                });
            }
        }
    }

    /**
     * These tables have no id to tell two copies of a row apart, so each
     * repeated pair is deleted and its earliest row put back.
     *
     * @param array{string, string} $pair
     */
    private function removeDuplicates(string $table, array $pair): void
    {
        $repeated = DB::table($table)
            ->select($pair)
            ->groupBy($pair)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $dated = Schema::hasColumn($table, 'created_at');

        foreach ($repeated as $row) {
            DB::transaction(function () use ($table, $pair, $row, $dated) {
                $rows = fn () => DB::table($table)
                    ->where($pair[0], $row->{$pair[0]})
                    ->where($pair[1], $row->{$pair[1]});

                $keep = $dated ? $rows()->orderBy('created_at')->first() : $rows()->first();

                $rows()->delete();
                DB::table($table)->insert((array) $keep);
            });
        }
    }
};
