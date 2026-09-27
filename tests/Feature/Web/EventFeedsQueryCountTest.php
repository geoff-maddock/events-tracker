<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Location;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The text feeds render up to 1,000 events; each row reads its venue's
 * location, tags, entities and series, so those are eager-loaded (#2172).
 */
class EventFeedsQueryCountTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeEvents(int $count, Tag $tag): void
    {
        for ($i = 0; $i < $count; $i++) {
            $event = Event::factory()->create(['start_at' => now()->addDays($i + 1)]);
            Location::factory()->create(['entity_id' => $event->venue_id]);
            $event->tags()->attach($tag->id);
            $event->entities()->attach(Entity::factory()->create()->id);
        }
    }

    private function queriesFor(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_feed_query_counts_do_not_grow_with_events(): void
    {
        $tag = Tag::factory()->create(['name' => 'Zzfeed', 'slug' => 'zzfeed']);
        $urls = ['/events/feed', '/events/brief-text', '/events/feed/tag/zzfeed'];

        $this->makeEvents(3, $tag);
        $few = array_map(fn ($u) => $this->queriesFor($u), $urls);

        $this->makeEvents(9, $tag);
        $many = array_map(fn ($u) => $this->queriesFor($u), $urls);

        foreach ($urls as $i => $url) {
            $this->assertLessThanOrEqual($few[$i] + 2, $many[$i], "{$url}: 3 events = {$few[$i]} queries, 12 events = {$many[$i]}");
        }
    }
}
