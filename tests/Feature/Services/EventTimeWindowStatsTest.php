<?php

namespace Tests\Feature\Services;

use App\Enums\EventTimeWindow;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Services\EventTimeWindowStats;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EventTimeWindowStatsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function insideStart(EventTimeWindow $window): string
    {
        $range = $window->range();

        return Carbon::parse($range['start'])->addMinutes(10)->format('Y-m-d H:i:s');
    }

    public function test_stats_count_public_events_only(): void
    {
        $window = EventTimeWindow::Tonight;
        $start = $this->insideStart($window);

        Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $start,
            'created_by' => User::factory()->create()->id,
        ]);
        Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'start_at' => $start,
            'created_by' => User::factory()->create()->id,
        ]);
        Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PROPOSAL,
            'start_at' => $start,
            'created_by' => User::factory()->create()->id,
        ]);

        $stats = app(EventTimeWindowStats::class)->stats($window);

        $this->assertSame(1, $stats['events']);
    }

    public function test_stats_counts_distinct_venues_and_top_tags(): void
    {
        $window = EventTimeWindow::Tonight;
        $start = $this->insideStart($window);

        $venue = Entity::factory()->create();
        $tagA = Tag::factory()->create(['name' => 'Techno']);
        $tagB = Tag::factory()->create(['name' => 'Punk']);

        $eventOne = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $start,
            'venue_id' => $venue->id,
            'created_by' => User::factory()->create()->id,
        ]);
        $eventTwo = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $start,
            'venue_id' => $venue->id,
            'created_by' => User::factory()->create()->id,
        ]);

        $eventOne->tags()->attach([$tagA->id, $tagB->id]);
        $eventTwo->tags()->attach([$tagA->id]);

        $stats = app(EventTimeWindowStats::class)->stats($window);

        $this->assertSame(2, $stats['events']);
        $this->assertSame(1, $stats['venues']); // same venue for both events
        $this->assertSame('Techno', $stats['tags'][0]); // most frequent first
        $this->assertContains('Punk', $stats['tags']);

        // tagLinks carries name/slug/count for the drill-down pills, in the
        // same frequency order the meta-description tags are derived from.
        $this->assertSame(
            ['name' => 'Techno', 'slug' => $tagA->slug, 'count' => 2],
            $stats['tagLinks'][0]
        );
        $this->assertContains(
            ['name' => 'Punk', 'slug' => $tagB->slug, 'count' => 1],
            $stats['tagLinks']
        );
    }

    public function test_tag_links_exclude_non_public_events(): void
    {
        $window = EventTimeWindow::Tonight;
        $start = $this->insideStart($window);

        $tag = Tag::factory()->create(['name' => 'Private Only Tag']);

        $private = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'start_at' => $start,
            'created_by' => User::factory()->create()->id,
        ]);
        $private->tags()->attach($tag->id);

        $stats = app(EventTimeWindowStats::class)->stats($window);

        $this->assertNotContains('Private Only Tag', array_column($stats['tagLinks'], 'name'));
    }

    public function test_cache_key_varies_by_window_start_date(): void
    {
        $stats = app(EventTimeWindowStats::class);

        $stats->stats(EventTimeWindow::Tonight);

        $tonightRange = EventTimeWindow::Tonight->range();
        $cacheKey = 'event-window-stats:v2:tonight:'.substr($tonightRange['start'], 0, 10);

        $this->assertTrue(Cache::has($cacheKey));

        // A different window (this-week) usually starts on the same date but is
        // a distinct cache entry keyed by window value + start date.
        $weekRange = EventTimeWindow::ThisWeek->range();
        $weekCacheKey = 'event-window-stats:v2:this-week:'.substr($weekRange['start'], 0, 10);

        $this->assertNotSame($cacheKey, $weekCacheKey);
    }
}
