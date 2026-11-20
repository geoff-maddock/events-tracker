<?php

namespace Tests\Feature;

use App\Models\ClickTrack;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The nightly prunes for data nothing else deletes (#2392).
 */
class HousekeepingTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_rollup_skips_a_day_whose_clicks_have_been_pruned(): void
    {
        $day = Carbon::today()->subDays(ClickTrack::RETENTION_DAYS + 5);

        $this->artisan('entities:rollup-stats', ['--date' => $day->toDateString()])
            ->expectsOutputToContain('skipped, older than the click retention period')
            ->assertSuccessful();
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
