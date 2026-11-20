<?php

namespace Tests\Feature;

use App\Models\ClickTrack;
use App\Models\Entity;
use App\Models\EntityStatDaily;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The nightly prunes for data nothing else deletes (#2392).
 */
class HousekeepingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachePath = sys_get_temp_dir().'/housekeeping-cache-'.uniqid();
        File::makeDirectory($this->cachePath);
        config(['cache.stores.file.path' => $this->cachePath]);
        Cache::forgetDriver('file');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->cachePath);
        Cache::forgetDriver('file');

        parent::tearDown();
    }

    public function test_expired_cache_files_are_deleted_and_live_ones_kept(): void
    {
        $store = Cache::store('file');
        $store->put('short', 'gone', 60);
        $store->put('long', 'kept', 7200);
        $store->forever('forever', 'kept');
        File::put($this->cachePath.'/.gitignore', "*\n!.gitignore\n");

        $this->travel(2)->minutes();

        $this->artisan('cache:prune-expired', ['--dry-run' => true])
            ->expectsOutputToContain('Would remove 1 expired cache file(s)')
            ->assertSuccessful();
        $this->assertCount(4, File::allFiles($this->cachePath, true));

        $this->artisan('cache:prune-expired')
            ->expectsOutputToContain('Removed 1 expired cache file(s)')
            ->assertSuccessful();

        $this->assertCount(3, File::allFiles($this->cachePath, true));
        $this->assertFileExists($this->cachePath.'/.gitignore');
        $this->assertSame('kept', $store->get('long'));
        $this->assertSame('kept', $store->get('forever'));
        $this->assertNull($store->get('short'));
    }

    public function test_cache_prune_does_nothing_without_a_cache_directory(): void
    {
        config(['cache.stores.file.path' => $this->cachePath.'/missing']);

        $this->artisan('cache:prune-expired')->assertSuccessful();
    }

    public function test_clicks_past_the_retention_period_are_deleted(): void
    {
        $old = ClickTrack::create(['clicked_at' => Carbon::today()->subDays(ClickTrack::RETENTION_DAYS + 1)]);
        $recent = ClickTrack::create(['clicked_at' => Carbon::today()->subDays(ClickTrack::RETENTION_DAYS - 1)]);

        $this->artisan('clicks:prune', ['--dry-run' => true])
            ->expectsOutputToContain('Would remove 1 click(s)')
            ->assertSuccessful();
        $this->assertDatabaseHas('click_tracks', ['id' => $old->id]);

        $this->artisan('clicks:prune')
            ->expectsOutputToContain('Removed 1 click(s)')
            ->assertSuccessful();

        $this->assertDatabaseMissing('click_tracks', ['id' => $old->id]);
        $this->assertDatabaseHas('click_tracks', ['id' => $recent->id]);
    }

    public function test_rollup_of_a_pruned_day_keeps_its_click_total_and_rebuilds_the_rest(): void
    {
        $day = Carbon::today()->subDays(ClickTrack::RETENTION_DAYS + 5)->setTime(12, 0);
        $entity = Entity::factory()->create();
        $other = Entity::factory()->create();

        // what an earlier rollup stored; the clicks behind it are gone
        foreach ([$entity, $other] as $stored) {
            DB::table('entity_stats_daily')->insert([
                'entity_id' => $stored->id, 'date' => $day->toDateString(),
                'views' => 4, 'follows' => 9, 'clicks' => 7, 'responses' => 9,
            ]);
        }

        $follow = new Follow();
        $follow->forceFill([
            'user_id' => User::factory()->create()->id,
            'object_type' => 'entity',
            'object_id' => $entity->id,
            'created_at' => $day,
            'updated_at' => $day,
        ])->save();

        $this->artisan('entities:rollup-stats', ['--date' => $day->toDateString()])
            ->expectsOutputToContain('stored click totals kept')
            ->assertSuccessful();

        $row = EntityStatDaily::where('entity_id', $entity->id)->whereDate('date', $day)->firstOrFail();
        $this->assertSame([4, 1, 7, 0], [$row->views, $row->follows, $row->clicks, $row->responses]);

        // an entity with nothing left that day is zeroed, apart from its clicks
        $row = EntityStatDaily::where('entity_id', $other->id)->whereDate('date', $day)->firstOrFail();
        $this->assertSame([4, 0, 7, 0], [$row->views, $row->follows, $row->clicks, $row->responses]);
    }

    public function test_rollup_still_rebuilds_clicks_inside_the_retention_period(): void
    {
        $day = Carbon::today()->subDays(ClickTrack::RETENTION_DAYS);
        $entity = Entity::factory()->create();
        DB::table('entity_stats_daily')->insert([
            'entity_id' => $entity->id, 'date' => $day->toDateString(), 'clicks' => 7,
        ]);

        $this->artisan('entities:rollup-stats', ['--date' => $day->toDateString()])
            ->doesntExpectOutputToContain('stored click totals kept')
            ->assertSuccessful();

        $this->assertSame(0, EntityStatDaily::where('entity_id', $entity->id)->firstOrFail()->clicks);
    }

    public function test_a_cache_file_another_process_holds_is_left_alone(): void
    {
        $store = Cache::store('file');
        $store->put('held', 'x', 60);
        $this->travel(2)->minutes();

        $file = File::allFiles($this->cachePath)[0]->getPathname();
        $handle = fopen($file, 'r');
        flock($handle, LOCK_EX);

        try {
            $this->artisan('cache:prune-expired')
                ->expectsOutputToContain('Removed 0 expired cache file(s)')
                ->assertSuccessful();
            $this->assertFileExists($file);
        } finally {
            fclose($handle);
        }

        $this->artisan('cache:prune-expired')->expectsOutputToContain('Removed 1 expired cache file(s)');
        $this->assertFileDoesNotExist($file);
    }

    public function test_the_click_index_migration_leaves_the_index_on_rollback(): void
    {
        $migration = require database_path('migrations/2026_11_20_000000_add_clicked_at_index_to_click_tracks_table.php');

        $migration->down();
        $this->assertTrue(Schema::hasIndex('click_tracks', ['clicked_at']));

        // and running it where the index exists is a no-op
        $migration->up();
        $this->assertTrue(Schema::hasIndex('click_tracks', ['clicked_at']));
    }

    public function test_the_prunes_are_scheduled(): void
    {
        $commands = collect($this->app->make(Schedule::class)->events())
            ->map(fn ($event) => $event->command)
            ->implode("\n");

        foreach (['cache:prune-expired', 'clicks:prune', 'queue:prune-failed', 'sanctum:prune-expired'] as $command) {
            $this->assertStringContainsString($command, $commands);
        }
    }
}
