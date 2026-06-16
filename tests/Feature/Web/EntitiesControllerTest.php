<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EntitiesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_index_loads(): void
    {
        $this->get('/entities')->assertOk();
    }

    public function test_show_loads_for_existing_entity(): void
    {
        $entity = Entity::factory()->create();

        $this->get('/entities/'.$entity->slug)->assertOk();
    }

    public function test_show_returns_404_for_missing_entity(): void
    {
        $this->get('/entities/does-not-exist-zz-'.uniqid())->assertNotFound();
    }

    public function test_create_form_requires_auth(): void
    {
        $response = $this->get('/entities/create');

        $response->assertStatus(302);
    }

    public function test_create_form_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/entities/create')->assertOk();
    }

    public function test_show_eager_loads_past_event_relations_without_n_plus_one(): void
    {
        $entity = Entity::factory()->create();

        // Several past events for the grid card, each with a primary photo, type and tags.
        for ($i = 1; $i <= 3; $i++) {
            $event = Event::factory()->create([
                'start_at' => now()->subMonths($i),
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            ]);
            $event->tags()->attach(Tag::factory()->create());
            $event->photos()->attach(Photo::factory()->create(['is_primary' => 1]));
            $entity->events()->attach($event);
        }

        DB::enableQueryLog();
        $this->get('/entities/'.$entity->slug)->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');

        // The per-event primary-photo lookup must be eager-loaded, not run once per event.
        $perEventPhotoQueries = $queries->filter(fn ($q) => str_contains($q, 'event_photo')
            && str_contains($q, 'is_primary')
            && str_contains($q, 'limit 1'));

        $this->assertLessThanOrEqual(
            1,
            $perEventPhotoQueries->count(),
            'Past-event primary photos should be eager-loaded, not queried per event (N+1).'
        );
    }
}
