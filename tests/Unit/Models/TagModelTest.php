<?php

namespace Tests\Unit\Models;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_route_key_name_is_slug(): void
    {
        $this->assertSame('slug', (new Tag())->getRouteKeyName());
    }

    public function test_entities_relation_returns_attached_entities(): void
    {
        $tag = Tag::factory()->create();
        $entity = Entity::factory()->create();
        $entity->tags()->attach($tag->id);

        $this->assertSame([$entity->id], $tag->fresh()->entities()->pluck('entities.id')->all());
    }

    public function test_events_relation_returns_attached_events(): void
    {
        $tag = Tag::factory()->create();
        $event = Event::factory()->create();
        $event->tags()->attach($tag->id);

        $this->assertSame([$event->id], $tag->fresh()->events()->pluck('events.id')->all());
    }

    public function test_threads_relation_returns_attached_threads(): void
    {
        $tag = Tag::factory()->create();
        $thread = Thread::factory()->create();
        $thread->tags()->attach($tag->id);

        $this->assertSame([$thread->id], $tag->fresh()->threads()->pluck('threads.id')->all());
    }

    public function test_series_relation_returns_attached_series(): void
    {
        $tag = Tag::factory()->create();
        $series = Series::factory()->create();
        $series->tags()->attach($tag->id);

        $this->assertSame([$series->id], $tag->fresh()->series()->pluck('series.id')->all());
    }

    public function test_future_events_returns_only_future_events_with_this_tag(): void
    {
        $tag = Tag::factory()->create();
        $future = Event::factory()->create(['start_at' => Carbon::now()->addDays(7)]);
        $past = Event::factory()->create(['start_at' => Carbon::now()->subDays(7)]);
        $future->tags()->attach($tag->id);
        $past->tags()->attach($tag->id);

        $results = $tag->fresh()->futureEvents();

        $this->assertTrue($results->pluck('id')->contains($future->id));
        $this->assertFalse($results->pluck('id')->contains($past->id));
    }
}
