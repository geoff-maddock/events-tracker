<?php

namespace Tests\Feature;

use App\Services\Database\SchemaSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaDiffTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_snapshot_describes_columns_indexes_and_foreign_keys(): void
    {
        $snapshot = SchemaSnapshot::capture(DB::connection(), DB::connection()->getDatabaseName());

        $this->assertArrayHasKey('users', $snapshot);
        $this->assertStringContainsString('varchar(255) not null', $snapshot['users']['columns']['email']);
        // display widths are dropped, so an old server's int(10) matches a new one's int
        $this->assertStringStartsWith('int unsigned not null', $snapshot['users']['columns']['id']);
        $this->assertStringEndsWith('auto_increment', $snapshot['users']['columns']['id']);
        $this->assertContains('primary key (id)', $snapshot['users']['indexes']);
        $this->assertContains('unique (email)', $snapshot['users']['indexes']);
        $this->assertNotEmpty($snapshot['event_showings']['foreign_keys']);
        $this->assertStringContainsString('(event_id) -> events (id)', implode("\n", $snapshot['event_showings']['foreign_keys']));
    }

    public function test_identical_snapshots_have_no_differences(): void
    {
        $snapshot = SchemaSnapshot::capture(DB::connection(), DB::connection()->getDatabaseName());

        $this->assertSame([], SchemaSnapshot::diff($snapshot, $snapshot));
    }

    public function test_differences_are_reported_in_both_directions(): void
    {
        $fresh = [
            'events' => [
                'columns' => ['id' => 'int unsigned not null auto_increment', 'slug' => 'varchar(255) not null', 'name' => 'varchar(255) not null'],
                'indexes' => ['primary key (id)', 'index (slug)'],
                'foreign_keys' => [],
            ],
            'tags' => ['columns' => ['id' => 'int not null'], 'indexes' => [], 'foreign_keys' => []],
        ];
        $prod = [
            'events' => [
                'columns' => ['id' => 'int unsigned not null auto_increment', 'slug' => 'varchar(255) null', 'legacy' => 'text null'],
                'indexes' => ['primary key (id)', 'unique (slug)'],
                'foreign_keys' => ['(series_id) -> series (id) on delete set null, on update no action'],
            ],
            'old_table' => ['columns' => ['id' => 'int not null'], 'indexes' => [], 'foreign_keys' => []],
        ];

        $differences = SchemaSnapshot::diff($fresh, $prod, 'a fresh migrate', 'prod');

        $this->assertSame(['events', 'old_table', 'tags'], array_keys($differences));
        $this->assertSame(['table is only in prod'], $differences['old_table']);
        $this->assertSame(['table is missing from prod'], $differences['tags']);
        $this->assertSame([
            'column name is missing from prod (varchar(255) not null)',
            'column legacy is only in prod (text null)',
            'column slug differs: a fresh migrate has varchar(255) not null; prod has varchar(255) null',
            'index (slug) is missing from prod',
            'unique (slug) is only in prod',
            'foreign key (series_id) -> series (id) on delete set null, on update no action is only in prod',
        ], $differences['events']);
    }

    public function test_the_command_passes_when_the_database_matches(): void
    {
        $database = DB::connection()->getDatabaseName();

        $this->artisan('db:schema-diff', ['--against' => $database])
            ->expectsOutputToContain('matches a fresh migrate')
            ->assertSuccessful();
    }

    public function test_the_command_fails_for_a_database_it_cannot_read(): void
    {
        $this->artisan('db:schema-diff', ['--against' => 'no_such_database_here'])
            ->expectsOutputToContain('No tables found in no_such_database_here')
            ->assertFailed();
    }
}
