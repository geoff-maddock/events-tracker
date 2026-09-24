<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the columns show pages, follow checks and popularity queries filter on (#2171).
 *
 * The four events indexes already exist on prod (as idx_slug, idx_start_at, idx_visibility_start,
 * idx_venue_start) but no migration created them, so fresh databases and CI never had them.
 * Every index is skipped when one on the same columns already exists, whatever it is named,
 * so this is a no-op for anything prod already has. Indexes this migration creates use
 * Laravel's default names, and down() drops only those.
 */
return new class extends Migration
{
    /**
     * table => list of column lists
     *
     * @var array<string, array<int, array<int, string>>>
     */
    private array $indexes = [
        'events' => [
            ['slug'],
            ['start_at'],
            ['visibility_id', 'start_at'],
            ['venue_id', 'start_at'],
        ],
        // not unique: prod has a duplicate entity slug ("karma", ids 291 and 296)
        'entities' => [['slug']],
        'series' => [['slug']],
        'comments' => [['commentable_type', 'commentable_id']],
        'follows' => [
            ['object_type', 'object_id'],
            ['user_id', 'object_type', 'object_id'],
        ],
        'likes' => [['object_type', 'object_id']],
        'activities' => [['user_id', 'action_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $columnSets) {
            foreach ($columnSets as $columns) {
                if ($this->hasIndexOn($table, $columns)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $columnSets) {
            foreach ($columnSets as $columns) {
                $name = $this->defaultName($table, $columns);

                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }

    /**
     * True when some index on $table starts with exactly these columns, in this order.
     *
     * @param  array<int, string>  $columns
     */
    private function hasIndexOn(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (array_slice($index['columns'], 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function defaultName(string $table, array $columns): string
    {
        return strtolower($table.'_'.implode('_', $columns).'_index');
    }
};
