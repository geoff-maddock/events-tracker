<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagShowPageTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    protected function makePublicEventWithTag(Tag $tag, array $attributes = []): Event
    {
        $event = Event::factory()->create(array_merge(['visibility_id' => 3], $attributes));
        $event->tags()->attach($tag->id);

        return $event;
    }

    /** @test */
    public function upcoming_events_show_before_past_events()
    {
        $tag = Tag::factory()->create();
        $future = $this->makePublicEventWithTag($tag, [
            'name' => 'Zeta Future Gathering',
            'start_at' => now()->addDays(3),
        ]);
        $past = $this->makePublicEventWithTag($tag, [
            'name' => 'Alpha Past Gathering',
            'start_at' => now()->subDays(3),
        ]);

        $response = $this->get("/tags/{$tag->slug}");

        $response->assertOk();
        $response->assertSeeInOrder(['Upcoming Events', $future->name, 'Past Events', $past->name]);
    }

    /** @test */
    public function upcoming_events_are_sorted_ascending_by_start_date()
    {
        $tag = Tag::factory()->create();
        $later = $this->makePublicEventWithTag($tag, [
            'name' => 'Distant Future Event',
            'start_at' => now()->addDays(10),
        ]);
        $sooner = $this->makePublicEventWithTag($tag, [
            'name' => 'Near Future Event',
            'start_at' => now()->addDays(2),
        ]);

        $response = $this->get("/tags/{$tag->slug}");

        $response->assertOk();
        $response->assertSeeInOrder([$sooner->name, $later->name]);
    }

    /** @test */
    public function past_events_are_sorted_descending_by_start_date()
    {
        $tag = Tag::factory()->create();
        $older = $this->makePublicEventWithTag($tag, [
            'name' => 'Long Ago Event',
            'start_at' => now()->subDays(10),
        ]);
        $recent = $this->makePublicEventWithTag($tag, [
            'name' => 'Recent Past Event',
            'start_at' => now()->subDays(2),
        ]);

        $response = $this->get("/tags/{$tag->slug}");

        $response->assertOk();
        $response->assertSeeInOrder([$recent->name, $older->name]);
    }

    /** @test */
    public function jump_links_and_section_anchors_render_for_populated_sections()
    {
        $tag = Tag::factory()->create();
        $this->makePublicEventWithTag($tag, ['start_at' => now()->addDays(3)]);
        $this->makePublicEventWithTag($tag, ['start_at' => now()->subDays(3)]);

        $response = $this->get("/tags/{$tag->slug}");

        $response->assertOk();
        $response->assertSee('href="#upcoming-events"', false);
        $response->assertSee('href="#past-events"', false);
        $response->assertSee('id="upcoming-events"', false);
        $response->assertSee('id="past-events"', false);
    }

    /** @test */
    public function empty_tag_shows_no_content_message_and_no_jump_links()
    {
        $tag = Tag::factory()->create();

        $response = $this->get("/tags/{$tag->slug}");

        $response->assertOk();
        $response->assertSee('No content found for this tag.');
        $response->assertDontSee('href="#upcoming-events"', false);
        $response->assertDontSee('href="#past-events"', false);
    }
}
