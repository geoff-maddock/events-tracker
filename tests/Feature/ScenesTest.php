<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Tag;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ScenesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    protected function firstSceneSlug(): string
    {
        return array_key_first(config('scenes'));
    }

    public function test_scene_show_is_ok_and_shows_only_matching_upcoming_public_events(): void
    {
        $slug = $this->firstSceneSlug();
        $scene = config("scenes.$slug");
        $sceneTag = Tag::factory()->create(['slug' => $scene['tags'][0]]);
        $otherTag = Tag::factory()->create(['slug' => 'scenes-test-unrelated-tag']);

        $matchingFutureEvent = Event::factory()->create([
            'name' => 'Scene Matching Future Event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addDays(5),
        ]);
        $matchingFutureEvent->tags()->attach($sceneTag->id);

        $untaggedFutureEvent = Event::factory()->create([
            'name' => 'Scene Untagged Future Event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addDays(5),
        ]);
        $untaggedFutureEvent->tags()->attach($otherTag->id);

        $matchingPastEvent = Event::factory()->create([
            'name' => 'Scene Matching Past Event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->subDays(5),
        ]);
        $matchingPastEvent->tags()->attach($sceneTag->id);

        $response = $this->get('/scenes/'.$slug);

        $response->assertOk();
        $response->assertSee('Scene Matching Future Event');
        $response->assertDontSee('Scene Untagged Future Event');
        $response->assertDontSee('Scene Matching Past Event');
    }

    public function test_unknown_scene_slug_is_404(): void
    {
        $response = $this->get('/scenes/not-a-real-scene');

        $response->assertNotFound();
    }

    public function test_scene_show_title_uses_config_and_brand_suffix_and_has_meta_description(): void
    {
        $slug = $this->firstSceneSlug();
        $scene = config("scenes.$slug");

        $response = $this->get('/scenes/'.$slug);

        $response->assertOk();
        $expectedTitle = e($scene['title']).' | '.config('app.app_name');
        $response->assertSee('<title>'.$expectedTitle.'</title>', false);
        $response->assertSee('<meta name="description" content="', false);
    }

    public function test_scene_show_includes_item_list_json_ld_when_events_exist(): void
    {
        $slug = $this->firstSceneSlug();
        $scene = config("scenes.$slug");
        $sceneTag = Tag::factory()->create(['slug' => $scene['tags'][0]]);

        $event = Event::factory()->create([
            'name' => 'Scene Json Ld Event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addDays(3),
        ]);
        $event->tags()->attach($sceneTag->id);

        $response = $this->get('/scenes/'.$slug);

        $response->assertOk();
        $response->assertSee('"@type": "ItemList"', false);
        $response->assertSee('Scene Json Ld Event', false);
    }

    public function test_scenes_index_lists_all_five_scene_names(): void
    {
        $response = $this->get('/scenes');

        $response->assertOk();
        foreach (config('scenes') as $scene) {
            $response->assertSee($scene['name']);
        }
    }

    public function test_every_configured_scene_has_a_copy_partial(): void
    {
        foreach (array_keys(config('scenes')) as $slug) {
            $this->assertTrue(
                View::exists('scenes.copy.'.$slug),
                "Missing copy partial for scene [$slug]: scenes.copy.$slug"
            );
        }
    }
}
