<?php

namespace Tests\Unit\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Link;
use App\Models\Location;
use App\Models\Photo;
use App\Models\Role;
use App\Models\Series;
use App\Models\Visibility;
use App\Services\EventSchema;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * The shared schema.org Event mapping.
 *
 * Built entirely from unsaved models with their relations set by hand, so
 * these run without touching the database — the class reads relations through
 * helpers that short-circuit on relationLoaded(), and pre-setting them is both
 * faster and a check that the eager-loaded path is the one being exercised.
 */
class EventSchemaTest extends TestCase
{
    /**
     * An event with every relation EventSchema touches already loaded and
     * empty, so nothing here can silently fall through to a query.
     */
    private function event(array $attributes = []): Event
    {
        $event = new Event(array_merge([
            'name' => 'Camp Gloom',
            'slug' => 'camp-gloom',
            'start_at' => '2026-08-01 21:00:00',
        ], $attributes));

        $event->setRelation('photos', new Collection());
        $event->setRelation('entities', new Collection());
        $event->setRelation('visibility', null);
        $event->setRelation('venue', null);
        $event->setRelation('promoter', null);
        $event->setRelation('series', null);

        return $event;
    }

    private function venue(string $name = 'Squirrel Hill Sports Bar', ?Location $location = null): Entity
    {
        $venue = new Entity(['name' => $name, 'slug' => 'squirrel-hill-sports-bar']);
        $venue->setRelation('links', new Collection());
        $venue->setRelation('photos', new Collection());
        $venue->setRelation('locations', new Collection($location ? [$location] : []));

        return $venue;
    }

    private function photo(string $path): Photo
    {
        $photo = new Photo(['path' => $path, 'thumbnail' => $path]);
        $photo->is_primary = 1;

        return $photo;
    }

    private function location(string $visibility, array $attributes = []): Location
    {
        $location = new Location(array_merge([
            'address_one' => '4305 Murray Ave',
            'city' => 'Pittsburgh',
            'state' => 'PA',
            'postcode' => '15217',
            'country' => 'US',
        ], $attributes));

        $location->setRelation('visibility', new Visibility(['name' => $visibility]));

        return $location;
    }

    private function performer(string $name, ?string $url = null): Entity
    {
        $entity = new Entity(['name' => $name, 'slug' => str($name)->slug()->value()]);
        $entity->setRelation('roles', new Collection([new Role(['slug' => 'dj', 'name' => 'DJ'])]));
        $entity->setRelation('links', new Collection($url ? [new Link(['url' => $url, 'is_primary' => 1])] : []));

        return $entity;
    }

    // -- dates ------------------------------------------------------------
    //
    // The reason this class routes through EventTime. config('app.timezone')
    // is a fixed UTC-5 offset with no daylight saving, so reading the cast
    // Carbon emits an instant an hour late from March to November — a
    // well-formed timestamp naming the wrong moment, which nothing but an
    // assertion like this one will catch.

    public function test_a_summer_start_date_carries_the_daylight_offset(): void
    {
        $schema = EventSchema::forEvent($this->event(['start_at' => '2026-08-01 21:00:00']));

        $this->assertSame('2026-08-01T21:00:00-04:00', $schema['startDate']);
    }

    public function test_a_winter_start_date_carries_the_standard_offset(): void
    {
        $schema = EventSchema::forEvent($this->event(['start_at' => '2026-01-15 21:00:00']));

        $this->assertSame('2026-01-15T21:00:00-05:00', $schema['startDate']);
    }

    public function test_end_date_falls_back_to_the_default_event_length(): void
    {
        $schema = EventSchema::forEvent($this->event(['start_at' => '2026-08-01 21:00:00']));

        $this->assertSame('2026-08-02T01:00:00-04:00', $schema['endDate']);
    }

    public function test_end_date_uses_the_stored_end_time_when_present(): void
    {
        $schema = EventSchema::forEvent($this->event([
            'start_at' => '2026-08-01 21:00:00',
            'end_at' => '2026-08-01 23:30:00',
        ]));

        $this->assertSame('2026-08-01T23:30:00-04:00', $schema['endDate']);
    }

    // -- status -----------------------------------------------------------

    public function test_a_scheduled_event_is_marked_scheduled(): void
    {
        $schema = EventSchema::forEvent($this->event());

        $this->assertSame('https://schema.org/EventScheduled', $schema['eventStatus']);
    }

    public function test_a_cancelled_at_timestamp_marks_the_event_cancelled(): void
    {
        $schema = EventSchema::forEvent($this->event(['cancelled_at' => '2026-07-30 12:00:00']));

        $this->assertSame('https://schema.org/EventCancelled', $schema['eventStatus']);
    }

    public function test_cancelled_visibility_marks_the_event_cancelled(): void
    {
        $event = $this->event();
        $event->setRelation('visibility', new Visibility(['name' => 'Cancelled']));

        $schema = EventSchema::forEvent($event);

        $this->assertSame('https://schema.org/EventCancelled', $schema['eventStatus']);
    }

    // -- location ---------------------------------------------------------

    public function test_a_public_venue_address_is_published(): void
    {
        $event = $this->event();
        $event->setRelation('venue', $this->venue(location: $this->location('Public')));

        $schema = EventSchema::forEvent($event);

        $this->assertSame('Squirrel Hill Sports Bar', $schema['location']['name']);
        $this->assertSame('4305 Murray Ave', $schema['location']['address']['streetAddress']);
        $this->assertSame('Pittsburgh', $schema['location']['address']['addressLocality']);
    }

    public function test_a_guarded_venue_address_is_withheld(): void
    {
        // JSON-LD is public output with no auth context, so a Guarded address
        // must not reach a crawler — the rule Entity::getJsonLd() applies to
        // the venue's own page.
        $event = $this->event();
        $event->setRelation('venue', $this->venue(location: $this->location('Guarded')));

        $schema = EventSchema::forEvent($event);

        $this->assertSame('Squirrel Hill Sports Bar', $schema['location']['name']);
        $this->assertArrayNotHasKey('address', $schema['location']);
    }

    public function test_an_event_with_no_venue_still_locates_to_the_city(): void
    {
        $schema = EventSchema::forEvent($this->event());

        $this->assertSame('TBA', $schema['location']['name']);
        $this->assertSame('Pittsburgh', $schema['location']['address']['addressLocality']);
    }

    public function test_a_venue_with_no_location_on_file_gets_a_city_level_address(): void
    {
        // Search Console's second-largest warning: a fifth of listed venues
        // have no Location row, and a Place with no address at all is worth
        // less than one locatable to the city.
        $event = $this->event();
        $event->setRelation('venue', $this->venue());

        $schema = EventSchema::forEvent($event);

        $this->assertSame('Squirrel Hill Sports Bar', $schema['location']['name']);
        $this->assertSame([
            '@type'           => 'PostalAddress',
            'addressLocality' => 'Pittsburgh',
            'addressRegion'   => 'PA',
            'addressCountry'  => 'US',
        ], $schema['location']['address']);
    }

    public function test_a_location_with_no_street_still_publishes_its_city(): void
    {
        $event = $this->event();
        $event->setRelation('venue', $this->venue(location: $this->location('Public', [
            'address_one' => null, 'city' => 'Millvale', 'postcode' => null,
        ])));

        $address = EventSchema::forEvent($event)['location']['address'];

        $this->assertArrayNotHasKey('streetAddress', $address);
        $this->assertArrayNotHasKey('postalCode', $address);
        $this->assertSame('Millvale', $address['addressLocality']);
        $this->assertSame('PA', $address['addressRegion']);
    }

    // -- offers -----------------------------------------------------------

    public function test_offer_url_prefers_the_ticket_link(): void
    {
        $schema = EventSchema::forEvent($this->event([
            'ticket_link' => 'https://tickets.example/camp-gloom',
            'primary_link' => 'https://example.test/camp-gloom',
        ]));

        $this->assertSame('https://tickets.example/camp-gloom', $schema['offers']['url']);
    }

    public function test_offer_url_falls_back_to_the_primary_link_then_the_event(): void
    {
        $schema = EventSchema::forEvent($this->event(['primary_link' => 'https://example.test/camp-gloom']));
        $this->assertSame('https://example.test/camp-gloom', $schema['offers']['url']);

        $schema = EventSchema::forEvent($this->event());
        $this->assertSame(route('events.show', 'camp-gloom'), $schema['offers']['url']);
    }

    public function test_offer_price_uses_the_door_price(): void
    {
        $schema = EventSchema::forEvent($this->event(['door_price' => '15.00']));

        $this->assertSame('15.00', $schema['offers']['price']);
        $this->assertSame('USD', $schema['offers']['priceCurrency']);
    }

    public function test_an_unknown_price_is_not_advertised_as_free(): void
    {
        // Google renders price 0 as "Free". A null price means nobody entered
        // one, which is not the same claim.
        $offer = EventSchema::forEvent($this->event())['offers'];

        $this->assertArrayNotHasKey('price', $offer);
        $this->assertArrayNotHasKey('priceCurrency', $offer);
        $this->assertSame('https://schema.org/InStock', $offer['availability']);
    }

    public function test_an_explicit_zero_price_is_free(): void
    {
        // '0.00', not '0': Event::setDoorPriceAttribute() treats a bare '0'
        // as empty and stores null, so an explicit free price arrives as '0.00'.
        $this->assertSame('0.00', EventSchema::forEvent($this->event(['door_price' => '0.00']))['offers']['price']);
    }

    public function test_a_ticket_link_that_is_not_a_url_is_not_used(): void
    {
        // Pre-validation rows hold values like these; Search Console reports
        // them as missing or invalid offer urls.
        foreach (['Ticketfly.com', 'Admission: Free', 'e', 'ttps://example.test/x', 'mailto:x@example.test', 'https://millvalемusicfestival.com'] as $junk) {
            $schema = EventSchema::forEvent($this->event(['ticket_link' => $junk, 'primary_link' => 'not a url either']));

            $this->assertSame(route('events.show', 'camp-gloom'), $schema['offers']['url'], "accepted: $junk");
        }

        // Surrounding whitespace is a data-entry slip, not a bad link.
        $schema = EventSchema::forEvent($this->event(['ticket_link' => ' https://example.test/x ']));
        $this->assertSame('https://example.test/x', $schema['offers']['url']);
    }

    // -- performer / organizer --------------------------------------------

    public function test_related_performers_are_listed_with_their_links(): void
    {
        $event = $this->event();
        $event->setRelation('entities', new Collection([
            $this->performer('DJ Strawberry Bloodbath', 'https://example.test/strawberry'),
            $this->performer('Zona Morta'),
        ]));

        $schema = EventSchema::forEvent($event);

        $this->assertCount(2, $schema['performer']);
        $this->assertSame('DJ Strawberry Bloodbath', $schema['performer'][0]['name']);
        $this->assertSame('https://example.test/strawberry', $schema['performer'][0]['url']);
        // No external link: the performer's own page here is still a url.
        $this->assertSame(route('entities.show', 'zona-morta'), $schema['performer'][1]['url']);
    }

    public function test_the_performer_limit_is_respected(): void
    {
        $event = $this->event();
        $event->setRelation('entities', new Collection([
            $this->performer('Aaa'), $this->performer('Bbb'), $this->performer('Ccc'),
        ]));

        $this->assertCount(2, EventSchema::forEvent($event, 2)['performer']);
    }

    public function test_performer_falls_back_to_the_event_itself(): void
    {
        $schema = EventSchema::forEvent($this->event(['primary_link' => 'https://example.test/camp-gloom']));

        $this->assertSame([[
            '@type' => 'PerformingGroup',
            'name' => 'Camp Gloom',
            'url' => 'https://example.test/camp-gloom',
        ]], $schema['performer']);
    }

    public function test_organizer_prefers_the_promoter_over_the_venue(): void
    {
        $promoter = new Entity(['name' => 'Gloom Collective', 'slug' => 'gloom-collective']);
        $promoter->setRelation('links', new Collection([new Link(['url' => 'https://example.test/gloom', 'is_primary' => 1])]));

        $event = $this->event();
        $event->setRelation('venue', $this->venue());
        $event->setRelation('promoter', $promoter);

        $schema = EventSchema::forEvent($event);

        $this->assertSame('Gloom Collective', $schema['organizer']['name']);
        $this->assertSame('https://example.test/gloom', $schema['organizer']['url']);
    }

    public function test_organizer_falls_back_to_the_venue(): void
    {
        $event = $this->event();
        $event->setRelation('venue', $this->venue());

        $this->assertSame('Squirrel Hill Sports Bar', EventSchema::forEvent($event)['organizer']['name']);
    }

    public function test_an_organizer_with_no_external_link_points_at_its_own_page(): void
    {
        // The single largest Search Console warning (1,884 items): an
        // Organization with a name and no url.
        $event = $this->event();
        $event->setRelation('venue', $this->venue());

        $this->assertSame(route('entities.show', 'squirrel-hill-sports-bar'), EventSchema::forEvent($event)['organizer']['url']);
    }

    public function test_organizer_falls_back_to_the_series_promoter_or_venue(): void
    {
        $promoter = new Entity(['name' => 'Gloom Collective', 'slug' => 'gloom-collective']);
        $promoter->setRelation('links', new Collection());

        $series = new Series(['name' => 'Gloom Nights']);
        $series->setRelation('promoter', $promoter);
        $series->setRelation('venue', null);

        $event = $this->event();
        $event->setRelation('series', $series);

        $this->assertSame('Gloom Collective', EventSchema::forEvent($event)['organizer']['name']);
    }

    public function test_an_event_with_neither_promoter_nor_venue_nor_series_omits_organizer(): void
    {
        $this->assertArrayNotHasKey('organizer', EventSchema::forEvent($this->event()));
    }

    // -- image ------------------------------------------------------------

    public function test_image_falls_back_through_series_and_venue_to_the_site_promo(): void
    {
        $event = $this->event();
        $this->assertSame([url(EventSchema::DEFAULT_IMAGE_PATH)], EventSchema::forEvent($event)['image']);

        $venue = $this->venue();
        $venue->setRelation('photos', new Collection([$this->photo('photos/venue.jpg')]));
        $event->setRelation('venue', $venue);
        $this->assertStringEndsWith('photos/venue.jpg', EventSchema::forEvent($event)['image'][0]);

        $series = new Series(['name' => 'Gloom Nights']);
        $series->setRelation('photos', new Collection([$this->photo('photos/series.jpg')]));
        $event->setRelation('series', $series);
        $this->assertStringEndsWith('photos/series.jpg', EventSchema::forEvent($event)['image'][0]);

        $event->setRelation('photos', new Collection([$this->photo('photos/flyer.jpg')]));
        $this->assertStringEndsWith('photos/flyer.jpg', EventSchema::forEvent($event)['image'][0]);
    }

    // -- description ------------------------------------------------------

    public function test_description_prefers_the_short_blurb_and_strips_markup(): void
    {
        $schema = EventSchema::forEvent($this->event([
            'short' => "A <b>goth</b> dance   party\nhosted by DJ Strawberry Bloodbath.",
            'description' => 'The long one.',
        ]));

        $this->assertSame('A goth dance party hosted by DJ Strawberry Bloodbath.', $schema['description']);
    }

    public function test_description_falls_back_to_the_long_description(): void
    {
        $schema = EventSchema::forEvent($this->event(['description' => 'The long one.']));

        $this->assertSame('The long one.', $schema['description']);
    }

    public function test_an_event_with_no_copy_describes_itself(): void
    {
        $event = $this->event(['start_at' => '2026-08-01 21:00:00']);
        $event->setRelation('venue', $this->venue());

        $this->assertSame(
            'Camp Gloom at Squirrel Hill Sports Bar on Saturday, August 1, 2026.',
            EventSchema::forEvent($event)['description']
        );

        $this->assertSame('Camp Gloom.', EventSchema::forEvent($this->event(['start_at' => null]))['description']);
    }

    public function test_an_event_with_no_start_time_omits_the_dates(): void
    {
        // Absent, not null: a null startDate reads as a malformed date.
        $schema = EventSchema::forEvent($this->event(['start_at' => null]));

        $this->assertArrayNotHasKey('startDate', $schema);
        $this->assertArrayNotHasKey('endDate', $schema);
    }

    // -- shape ------------------------------------------------------------

    public function test_the_standalone_document_carries_the_context(): void
    {
        $document = EventSchema::document($this->event());

        $this->assertSame('https://schema.org', $document['@context']);
        $this->assertSame('Event', $document['@type']);
    }

    public function test_the_embeddable_node_omits_the_context(): void
    {
        $this->assertArrayNotHasKey('@context', EventSchema::forEvent($this->event()));
    }

    public function test_it_encodes_to_valid_json_with_hostile_copy(): void
    {
        // The failure the hand-written template had: e() escapes quotes but
        // leaves newlines and backslashes, and an unescaped control character
        // inside a JSON string is invalid.
        $schema = EventSchema::forEvent($this->event([
            'name' => 'Sound\\Text "Experiments"',
            'short' => "line one\nline two \\ and a \"quote\"",
        ]));

        $json = json_encode($schema);

        $this->assertIsString($json);
        $this->assertSame($schema, json_decode($json, true));
    }
}
