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
        // Scoped with assertSeeInOrder so this can't be satisfied by the
        // (visibility-gated) Locations card lower in the sidebar instead of
        // the Venue Facts card.
        $response->assertSeeInOrder(['Venue Facts', '650', 'Millvale Business District']);
    }

    public function test_guarded_venue_location_hides_address_and_capacity_from_guests(): void
    {
        $venue = $this->makeVenue(['name' => 'The Secret Speakeasy']);
        Location::factory()->create([
            'entity_id' => $venue->id,
            'address_one' => '999 Secret Alley',
            'city' => 'Pittsburgh',
            'capacity' => 4321,
            'neighborhood' => 'Undisclosed Neighborhood',
            'visibility_id' => Visibility::VISIBILITY_GUARDED,
        ]);

        // Guest (not signed in): the Guarded location's street address and
        // capacity must not leak into the facts panel, matching the
        // existing Locations card's Guarded gating. This also covers
        // Entity::getJsonLd(), which gates its streetAddress and
        // maximumAttendeeCapacity fields on the same Guarded check -- so the
        // assertions below run against the full response body, JSON-LD
        // included, rather than stripping the <script type="application/ld+json">
        // blocks first.
        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();

        $response->assertDontSee('999 Secret Alley', false);
        $response->assertDontSee('4321', false);
        $response->assertDontSee('Undisclosed Neighborhood', false);
    }

    public function test_seo_description_omits_neighborhood_for_guarded_location(): void
    {
        $venue = $this->makeVenue(['name' => 'The Guarded Room']);
        Location::factory()->create([
            'entity_id' => $venue->id,
            'city' => 'Pittsburgh',
            'neighborhood' => 'Undisclosed Neighborhood',
            'visibility_id' => Visibility::VISIBILITY_GUARDED,
        ]);
        $venue = $venue->fresh(['roles', 'locations.visibility']);

        $description = $venue->getSeoDescriptionFormat();

        $this->assertStringNotContainsString('Undisclosed Neighborhood', $description);
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

    public function test_past_events_render_after_description_at_the_bottom(): void
    {
        $descriptionMarker = 'ZZDESC-' . uniqid() . ' history of the room.';
        $venue = $this->makeVenue([
            'name' => 'The Bottom Room',
            'description' => $descriptionMarker,
        ]);

        $pastEvent = Event::factory()->create([
            'name' => 'ZZPASTEVENT-' . uniqid(),
            'venue_id' => $venue->id,
            'start_at' => Carbon::now()->subDays(3),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        // The Upcoming Events grid moved to the top, but the past-events grid
        // must stay at the bottom of the page, after the description.
        $response->assertSeeInOrder([$descriptionMarker, 'Past Events', $pastEvent->name], false);
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

    public function test_private_event_linked_only_by_venue_id_is_hidden_from_guests(): void
    {
        $venue = $this->makeVenue(['name' => 'Preserving Underground Private']);

        $privateEvent = Event::factory()->create([
            'name' => 'ZZPRIVATEVENUEONLY-' . uniqid(),
            'venue_id' => $venue->id,
            'start_at' => Carbon::now()->addDays(2),
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
        ]);

        // Deliberately not attached via the entity_event pivot -- this is the
        // "venue_id only" case the union query surfaces, and it must still
        // respect visibility for guests.
        $this->assertFalse($privateEvent->entities()->where('entities.id', $venue->id)->exists());

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        $response->assertDontSee($privateEvent->name);
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

    public function test_upcoming_events_grid_breaks_to_one_column_below_lg(): void
    {
        $venue = $this->makeVenue(['name' => 'Grid Test Hall']);

        $event = Event::factory()->create([
            'name' => 'ZZGRID-' . uniqid(),
            'venue_id' => $venue->id,
            'start_at' => Carbon::now()->addDays(3),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        $event->entities()->attach($venue->id);

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        $response->assertSee('grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4', false);
    }

    public function test_upcoming_events_date_filter_is_removed(): void
    {
        $venue = $this->makeVenue(['name' => 'No Filter Hall']);

        $response = $this->get('/entities/' . $venue->slug);

        $response->assertOk();
        $response->assertDontSee('From date:', false);
        $response->assertDontSee('name="start_at"', false);
    }
}
