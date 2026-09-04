<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prod never had a unique index on tags.slug, and every tag_list resolution
 * path created a fresh tag for any non-id value, so duplicate tags crept in
 * (issue #2120). Merge any remaining duplicates onto the lowest id, then add
 * the unique index so the database enforces it from here on.
 */
return new class extends Migration
{
    private const INDEX = 'tags_slug_unique';

    /**
     * Pivot tables keyed by tag_id => the column naming the other side.
     *
     * @var array<string, string>
     */
    private array $pivots = [
        'event_tag' => 'event_id',
        'entity_tag' => 'entity_id',
        'series_tag' => 'series_id',
        'blog_tag' => 'blog_id',
        'post_tag' => 'post_id',
        'tag_thread' => 'thread_id',
    ];

    public function up(): void
    {
        $this->mergeDuplicateTags();

        if (! $this->indexExists()) {
            Schema::table('tags', function (Blueprint $table) {
                $table->unique('slug', self::INDEX);
            });
        }
    }

    public function down(): void
    {
        // The merge is not reversible; only the index is dropped.
        if ($this->indexExists()) {
            Schema::table('tags', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        }
    }

    private function mergeDuplicateTags(): void
    {
        $groups = DB::table('tags')
            ->select('slug', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as n'))
            ->groupBy('slug')
            ->having('n', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $keepId = (int) $group->keep_id;
            $dupIds = DB::table('tags')
                ->where('slug', $group->slug)
                ->where('id', '!=', $keepId)
                ->pluck('id')
                ->all();

            DB::transaction(function () use ($keepId, $dupIds) {
                foreach ($this->pivots as $table => $otherKey) {
                    if (Schema::hasTable($table)) {
                        $this->remapPivot($table, $otherKey, $keepId, $dupIds);
                    }
                }

                $this->remapFollows($keepId, $dupIds);

                DB::table('tags')->whereIn('id', $dupIds)->delete();
            });
        }
    }

    /**
     * Point pivot rows at the kept tag, skipping any parent already linked to
     * it, then drop whatever still references a duplicate.
     *
     * @param  list<int> $dupIds
     */
    private function remapPivot(string $table, string $otherKey, int $keepId, array $dupIds): void
    {
        $rows = DB::table($table)->whereIn('tag_id', $dupIds)->get();

        foreach ($rows as $row) {
            $linked = DB::table($table)
                ->where('tag_id', $keepId)
                ->where($otherKey, $row->{$otherKey})
                ->exists();

            if (! $linked) {
                $data = (array) $row;
                unset($data['id']);
                $data['tag_id'] = $keepId;
                DB::table($table)->insert($data);
            }
        }

        DB::table($table)->whereIn('tag_id', $dupIds)->delete();
    }

    /**
     * @param  list<int> $dupIds
     */
    private function remapFollows(int $keepId, array $dupIds): void
    {
        $rows = DB::table('follows')
            ->where('object_type', 'tag')
            ->whereIn('object_id', $dupIds)
            ->get();

        foreach ($rows as $row) {
            $linked = DB::table('follows')
                ->where('object_type', 'tag')
                ->where('object_id', $keepId)
                ->where('user_id', $row->user_id)
                ->exists();

            if ($linked) {
                DB::table('follows')->where('id', $row->id)->delete();
            } else {
                DB::table('follows')->where('id', $row->id)->update(['object_id' => $keepId]);
            }
        }
    }

    private function indexExists(): bool
    {
        return count(DB::select('SHOW INDEX FROM `tags` WHERE Key_name = ?', [self::INDEX])) > 0;
    }
};
