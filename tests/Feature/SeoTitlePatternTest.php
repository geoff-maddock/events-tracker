<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Tag;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTitlePatternTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    protected function appName(): string
    {
        return config('app.app_name');
    }

    public function test_home_page_title_is_query_forward(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('| '.$this->appName().'</title>', false);
        $response->assertDontSee("\u{2022} ".$this->appName().'</title>', false);
    }

    public function test_events_index_title_is_query_forward_and_generic(): void
    {
        $response = $this->get('/events');

        $response->assertOk();
        $response->assertSee('| '.$this->appName().'</title>', false);
        $response->assertDontSee("\u{2022} ".$this->appName().'</title>', false);
        $response->assertSee('Pittsburgh Events Calendar', false);
    }

    public function test_event_show_title_is_query_forward(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $response = $this->get('/events/'.$event->id);

        $response->assertOk();
        $response->assertSee('| '.$this->appName().'</title>', false);
        $response->assertDontSee("\u{2022} ".$this->appName().'</title>', false);
    }

    public function test_tag_show_page_renders_tag_description(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Description Probe',
            'slug' => 'description-probe',
            'description' => 'A tag used only to verify the description renders.',
        ]);

        $response = $this->get('/tags/'.$tag->slug);

        $response->assertOk();
        $response->assertSee('A tag used only to verify the description renders.', false);
    }
}
