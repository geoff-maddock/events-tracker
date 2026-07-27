<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyListingSeoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_unknown_taxonomy_terms_return_404(): void
    {
        $this->get('/threads/tag/definitely-not-a-tag')->assertNotFound();
        $this->get('/posts/tag/definitely-not-a-tag')->assertNotFound();
        $this->get('/tags/definitely-not-a-tag')->assertNotFound();
        $this->get('/events/type/definitely-not-a-type')->assertNotFound();
        $this->get('/events/series/definitely-not-a-series')->assertNotFound();
        $this->get('/events/grid/type/definitely-not-a-type')->assertNotFound();
        $this->get('/events/grid/series/definitely-not-a-series')->assertNotFound();
    }

    public function test_existing_but_empty_tag_page_is_noindexed(): void
    {
        Tag::factory()->create(['name' => 'Empty Probe', 'slug' => 'empty-probe']);

        $response = $this->get('/tags/empty-probe');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_tag_with_content_is_indexable(): void
    {
        $tag = Tag::factory()->create(['name' => 'Full Probe', 'slug' => 'full-probe']);
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addWeek(),
        ]);
        $event->tags()->attach($tag);

        $response = $this->get('/tags/full-probe');

        $response->assertOk();
        $response->assertDontSee('noindex', false);
    }

    public function test_empty_thread_tag_listing_is_noindexed(): void
    {
        Tag::factory()->create(['name' => 'Threadless', 'slug' => 'threadless']);

        $response = $this->get('/threads/tag/threadless');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_empty_event_type_listing_is_noindexed(): void
    {
        EventType::factory()->create(['name' => 'Ghost Type', 'slug' => 'ghost-type']);

        $response = $this->get('/events/type/ghost-type');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_event_tag_listing_with_events_is_indexable(): void
    {
        $tag = Tag::factory()->create(['name' => 'Busy Tag', 'slug' => 'busy-tag']);
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addWeek(),
        ]);
        $event->tags()->attach($tag);

        $response = $this->get('/events/tag/busy-tag');

        $response->assertOk();
        $response->assertDontSee('noindex', false);
    }

    public function test_thread_brief_renders_tag_links_from_slug(): void
    {
        $tag = Tag::factory()->create(['name' => 'Hip Hop', 'slug' => 'hip-hop']);
        $thread = Thread::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $thread->tags()->attach($tag);

        $response = $this->get('/threads/'.$thread->id);

        $response->assertOk();
        $response->assertDontSee('/tags/Hip%20Hop', false);
        $response->assertDontSee('/threads/tag/Hip+Hop', false);
    }
}
