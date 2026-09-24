<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The indexes show pages, follow checks and popularity queries rely on (#2171).
 * Checked by columns, since prod has some of them under older names.
 */
class PerformanceIndexesTest extends TestCase
{
    use RefreshDatabase;

    public static function indexedColumns(): array
    {
        return [
            'events slug' => ['events', ['slug']],
            'events start_at' => ['events', ['start_at']],
            'events visibility + start' => ['events', ['visibility_id', 'start_at']],
            'events venue + start' => ['events', ['venue_id', 'start_at']],
            'entities slug' => ['entities', ['slug']],
            'series slug' => ['series', ['slug']],
            'comments morph' => ['comments', ['commentable_type', 'commentable_id']],
            'follows object' => ['follows', ['object_type', 'object_id']],
            'follows user + object' => ['follows', ['user_id', 'object_type', 'object_id']],
            'likes object' => ['likes', ['object_type', 'object_id']],
            'activities user + action' => ['activities', ['user_id', 'action_id']],
        ];
    }

    #[DataProvider('indexedColumns')]
    public function test_index_exists(string $table, array $columns): void
    {
        $found = collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => array_slice($index['columns'], 0, count($columns)) === $columns);

        $this->assertTrue($found, "No index on {$table}(".implode(', ', $columns).')');
    }
}
