<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Location;
use App\Models\Role;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueEntityPageTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function venueRole(): Role
    {
        return Role::where('name', 'Venue')->firstOrFail();
    }

    private function makeVenue(array $attrs = []): Entity
    {
        $venue = Entity::factory()->create(array_merge([
            'entity_status_id' => EntityStatus::ACTIVE,
            'short' => '',
        ], $attrs));
        $venue->roles()->attach($this->venueRole());

        return $venue->fresh(['roles']);
    }

    public function test_venue_title_includes_upcoming_events_and_city(): void
    {
        $venue = $this->makeVenue(['name' => 'The Rex Theater']);
        Location::factory()->create([
            'entity_id' => $venue->id,
            'city' => 'Pittsburgh',
            'capacity' => 700,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        $expectedTitle = e('The Rex Theater — Upcoming Events & Concerts in Pittsburgh') . ' | ' . config('app.app_name');
        $response->assertSee('<title>' . $expectedTitle . '</title>', false);
    }

    public function test_non_venue_entity_title_is_unchanged(): void
    {
        $entity = Entity::factory()->create([
            'entity_status_id' => EntityStatus::ACTIVE,
            'name' => 'Some Touring DJ',
            'short' => '',
        ]);

        $response = $this->get('/entities/' . $entity->slug);

        $response->assertOk();
        $rawTitle = $entity->getSeoTitleFormat();
        $this->assertStringNotContainsString('Upcoming Events & Concerts in', $rawTitle);
        $response->assertSee('<title>' . e($rawTitle) . ' | ' . config('app.app_name') . '</title>', false);
    }

    public function test_venue_facts_panel_renders_capacity_and_neighborhood(): void
    {
        $venue = $this->makeVenue(['name' => 'Mr Smalls Theatre']);
        Location::factory()->create([
            'entity_id' => $venue->id,
            'city' => 'Millvale',
            'state' => 'PA',
            'capacity' => 650,
            'neighborhood' => 'Millvale Business District',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        $response->assertSee('Venue Facts');
        $response->assertSee('650');
        $response->assertSee('Millvale Business District');
    }

    public function test_upcoming_events_render_before_description(): void
    {
        $descriptionMarker = 'ZZDESC-' . uniqid() . ' full history of the room.';
        $venue = $this->makeVenue([
            'name' => 'Spirit Hall',
            'description' => $descriptionMarker,
        ]);

        $event = Event::factory()->create([
            'name' => 'ZZEVENT-' . uniqid(),
            'venue_id' => $venue->id,
            'start_at' => Carbon::now()->addDays(3),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'min_age' => 21,
        ]);
        $event->entities()->attach($venue->id);

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        $response->assertSeeInOrder([$event->name, $descriptionMarker], false);
    }

    public function test_event_linked_only_by_venue_id_appears_on_venue_page(): void
    {
        $venue = $this->makeVenue(['name' => 'Preserving Underground']);

        $event = Event::factory()->create([
            'name' => 'ZZVENUEONLY-' . uniqid(),
            'venue_id' => $venue->id,
            'start_at' => Carbon::now()->addDays(2),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        // Deliberately not attached via the entity_event pivot -- this is the
        // "venue_id only" case the union query needs to surface.
        $this->assertFalse($event->entities()->where('entities.id', $venue->id)->exists());

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        $response->assertSee($event->name);
    }

    public function test_age_policy_is_21_plus_when_all_recent_events_require_21(): void
    {
        $venue = $this->makeVenue(['name' => 'ZZ Club TwentyOne']);

        for ($i = 0; $i < 3; $i++) {
            Event::factory()->create([
                'venue_id' => $venue->id,
                'min_age' => 21,
                'start_at' => Carbon::now()->addDays($i + 1),
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            ]);
        }

        $this->assertSame('21+', $venue->getAgePolicy());

        $response = $this->get('/entities/' . $venue->slug);
        $response->assertOk();
        $response->assertSee('21+');
    }

    public function test_age_policy_is_varies_by_event_when_mixed(): void
    {
        $venue = $this->makeVenue(['name' => 'ZZ Club Mixed']);

        Event::factory()->create([
            'venue_id' => $venue->id,
            'min_age' => null,
            'start_at' => Carbon::now()->addDay(),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        Event::factory()->create([
            'venue_id' => $venue->id,
            'min_age' => 21,
            'start_at' => Carbon::now()->addDays(2),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->assertSame('Varies by event', $venue->getAgePolicy());

        $response = $this->get('/entities/' . $venue->slug);
        $response->assertOk();
        $response->assertSee('Varies by event');
    }

    public function test_json_ld_includes_music_venue_type_and_max_capacity(): void
    {
        $venue = $this->makeVenue(['name' => 'ZZ JSON Venue']);
        Location::factory()->create([
            'entity_id' => $venue->id,
            'city' => 'Pittsburgh',
            'address_one' => '123 Main St',
            'capacity' => 500,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        Event::factory()->create([
            'venue_id' => $venue->id,
            'start_at' => Carbon::now()->addDays(5),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        $response->assertSee('"@type": "MusicVenue"', false);
        $response->assertSee('"maximumAttendeeCapacity": 500', false);
        $response->assertSee('"@type": "Event"', false);
    }
}
