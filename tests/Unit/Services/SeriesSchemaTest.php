<?php

namespace Tests\Unit\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Link;
use App\Models\Location;
use App\Models\OccurrenceType;
use App\Models\Photo;
use App\Models\Role;
use App\Models\Series;
use App\Models\Visibility;
use App\Services\EventSchema;
use App\Services\SeriesSchema;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * The schema.org EventSeries mapping. Built from unsaved models with their
 * relations set by hand, as EventSchemaTest does, so nothing here touches
 * the database.
 */
class SeriesSchemaTest extends TestCase
{
    private function series(array $attributes = [], ?string $occurrence = 'Weekly'): Series
    {
        $series = new Series(array_merge([
            'name' => 'Gloom Nights',
            'slug' => 'gloom-nights',
            'start_at' => '2026-01-05 21:00:00',
            'end_at' => '2026-01-06 02:00:00',
            'founded_at' => '2026-01-05 21:00:00',
        ], $attributes));

        $series->setRelation('photos', new Collection());
        $series->setRelation('entities', new Collection());
        $series->setRelation('venue', null);
        $series->setRelation('promoter', null);
        $series->setRelation('upcomingEvent', null);
        $series->setRelation('occurrenceType', $occurrence ? new OccurrenceType(['name' => $occurrence]) : null);

        return $series;
    }

    private function venue(?Location $location = null): Entity
    {
        $venue = new Entity(['name' => 'Squirrel Hill Sports Bar', 'slug' => 'squirrel-hill-sports-bar']);
        $venue->setRelation('links', new Collection());
        $venue->setRelation('photos', new Collection());
        $venue->setRelation('locations', new Collection($location ? [$location] : []));

        return $venue;
    }

    private function location(): Location
    {
        $location = new Location([
            'address_one' => '4305 Murray Ave',
            'city' => 'Pittsburgh',
            'state' => 'PA',
            'postcode' => '15217',
            'country' => 'US',
        ]);
        $location->setRelation('visibility', new Visibility(['name' => 'Public']));

        return $location;
    }

    private function event(array $attributes = []): Event
    {
        $event = new Event(array_merge([
            'name' => 'Gloom Nights: March',
            'slug' => 'gloom-nights-march',
            'start_at' => '2026-08-03 21:00:00',
        ], $attributes));

        $event->setRelation('photos', new Collection());
        $event->setRelation('entities', new Collection());
        $event->setRelation('visibility', null);
        $event->setRelation('venue', null);
        $event->setRelation('promoter', null);
        $event->setRelation('series', null);

        return $event;
    }

    private function performer(string $name): Entity
    {
        $entity = new Entity(['name' => $name, 'slug' => str($name)->slug()->value()]);
        $entity->setRelation('roles', new Collection([new Role(['slug' => 'dj', 'name' => 'DJ'])]));
        $entity->setRelation('links', new Collection());

        return $entity;
    }

    // -- dates ------------------------------------------------------------

    public function test_dates_come_from_the_next_instantiated_event(): void
    {
        $series = $this->series();
        $series->setRelation('upcomingEvent', $this->event(['start_at' => '2026-08-03 21:00:00', 'end_at' => '2026-08-04 01:00:00']));

        $schema = SeriesSchema::forSeries($series);

        $this->assertSame('2026-08-03T21:00:00-04:00', $schema['startDate']);
        $this->assertSame('2026-08-04T01:00:00-04:00', $schema['endDate']);
    }

    public function test_dates_project_from_the_schedule_when_no_event_exists_yet(): void
    {
        // Weekly from Monday 2026-01-05 at 9 PM: the next Monday on or after
        // today, at 9 PM Pittsburgh time, running until 2 AM.
        $schema = SeriesSchema::forSeries($this->series());

        $expected = CarbonImmutable::parse('2026-01-05 21:00:00', 'America/New_York');
        $today = CarbonImmutable::now('America/New_York')->startOfDay();
        while ($expected->lt($today)) {
            $expected = $expected->addWeek();
        }

        $this->assertSame($expected->toAtomString(), $schema['startDate']);
        $this->assertSame($expected->addHours(5)->toAtomString(), $schema['endDate']);
    }

    public function test_a_series_with_no_schedule_omits_the_dates(): void
    {
        $schema = SeriesSchema::forSeries($this->series(occurrence: 'No Schedule'));

        $this->assertArrayNotHasKey('startDate', $schema);
        $this->assertArrayNotHasKey('endDate', $schema);
    }

    public function test_a_cancelled_series_is_cancelled_and_has_no_projected_dates(): void
    {
        $schema = SeriesSchema::forSeries($this->series(['cancelled_at' => '2026-07-01 12:00:00']));

        $this->assertSame('https://schema.org/EventCancelled', $schema['eventStatus']);
        $this->assertArrayNotHasKey('startDate', $schema);
    }

    public function test_a_scheduled_series_is_scheduled(): void
    {
        $this->assertSame('https://schema.org/EventScheduled', SeriesSchema::forSeries($this->series())['eventStatus']);
    }

    // -- the recommended set ----------------------------------------------

    public function test_every_recommended_event_property_is_present(): void
    {
        // The Search Console block this class exists to clear: 48 public
        // series each missing endDate, offers, eventStatus and performer.
        $schema = SeriesSchema::forSeries($this->series());

        foreach (['startDate', 'endDate', 'eventStatus', 'eventAttendanceMode', 'image', 'description', 'location', 'offers', 'performer'] as $field) {
            $this->assertArrayHasKey($field, $schema, "missing $field");
        }
        $this->assertSame('EventSeries', $schema['@type']);
    }

    public function test_offers_follow_the_event_rules(): void
    {
        $offer = SeriesSchema::forSeries($this->series(['ticket_link' => 'Ticketfly.com']))['offers'];
        $this->assertSame(route('series.show', 'gloom-nights'), $offer['url']);
        $this->assertArrayNotHasKey('price', $offer);

        $offer = SeriesSchema::forSeries($this->series(['ticket_link' => 'https://tickets.example/gloom', 'door_price' => '10.00']))['offers'];
        $this->assertSame('https://tickets.example/gloom', $offer['url']);
        $this->assertSame('10.00', $offer['price']);
        $this->assertSame('USD', $offer['priceCurrency']);
    }

    public function test_performers_are_the_lineup_or_the_series_itself(): void
    {
        $series = $this->series();
        $this->assertSame([['@type' => 'PerformingGroup', 'name' => 'Gloom Nights']], SeriesSchema::forSeries($series)['performer']);

        $series->setRelation('entities', new Collection([$this->performer('Zona Morta'), $this->performer('Cutups')]));
        $performers = SeriesSchema::forSeries($series)['performer'];

        $this->assertSame(['Cutups', 'Zona Morta'], array_column($performers, 'name'));
        $this->assertSame(route('entities.show', 'cutups'), $performers[0]['url']);
    }

    public function test_organizer_prefers_the_promoter_and_always_has_a_url(): void
    {
        $promoter = new Entity(['name' => 'Gloom Collective', 'slug' => 'gloom-collective']);
        $promoter->setRelation('links', new Collection([new Link(['url' => 'https://example.test/gloom', 'is_primary' => 1])]));

        $series = $this->series();
        $series->setRelation('venue', $this->venue());
        $this->assertSame([
            '@type' => 'Organization',
            'name' => 'Squirrel Hill Sports Bar',
            'url' => route('entities.show', 'squirrel-hill-sports-bar'),
        ], SeriesSchema::forSeries($series)['organizer']);

        $series->setRelation('promoter', $promoter);
        $this->assertSame('https://example.test/gloom', SeriesSchema::forSeries($series)['organizer']['url']);
    }

    public function test_location_carries_the_venue_address(): void
    {
        $series = $this->series();
        $series->setRelation('venue', $this->venue($this->location()));

        $location = SeriesSchema::forSeries($series)['location'];

        $this->assertSame('Squirrel Hill Sports Bar', $location['name']);
        $this->assertSame('4305 Murray Ave', $location['address']['streetAddress']);
    }

    public function test_a_venueless_instance_inherits_the_series_venue_with_its_address(): void
    {
        // The old template inherited only the venue name, which is where the
        // "missing address" count on series pages came from.
        $series = $this->series();
        $series->setRelation('venue', $this->venue($this->location()));

        $node = SeriesSchema::subEvent($series, $this->event());

        $this->assertSame('Event', $node['@type']);
        $this->assertSame('Squirrel Hill Sports Bar', $node['location']['name']);
        $this->assertSame('4305 Murray Ave', $node['location']['address']['streetAddress']);
    }

    public function test_image_falls_back_through_the_venue_to_the_site_promo(): void
    {
        $series = $this->series();
        $this->assertSame([url(EventSchema::DEFAULT_IMAGE_PATH)], SeriesSchema::forSeries($series)['image']);

        $photo = new Photo(['path' => 'photos/venue.jpg', 'thumbnail' => 'photos/venue.jpg']);
        $photo->is_primary = 1;
        $venue = $this->venue();
        $venue->setRelation('photos', new Collection([$photo]));
        $series->setRelation('venue', $venue);
        $this->assertStringEndsWith('photos/venue.jpg', SeriesSchema::forSeries($series)['image'][0]);
    }

    public function test_description_falls_back_to_the_schedule_and_venue(): void
    {
        $series = $this->series();
        $series->setRelation('venue', $this->venue());

        $this->assertSame('Gloom Nights, a weekly series at Squirrel Hill Sports Bar.', SeriesSchema::forSeries($series)['description']);
        $this->assertSame('A goth night.', SeriesSchema::forSeries($this->series(['short' => 'A <b>goth</b> night.']))['description']);
    }

    // -- shape ------------------------------------------------------------

    public function test_the_document_carries_the_context_and_upcoming_instances(): void
    {
        $document = SeriesSchema::document($this->series(), [$this->event()]);

        $this->assertSame('https://schema.org', $document['@context']);
        $this->assertCount(1, $document['subEvent']);
        $this->assertSame('Gloom Nights: March', $document['subEvent'][0]['name']);
        $this->assertArrayNotHasKey('@context', $document['subEvent'][0]);
        $this->assertArrayNotHasKey('subEvent', SeriesSchema::document($this->series()));
    }

    public function test_sub_events_are_capped(): void
    {
        $events = array_fill(0, SeriesSchema::SUB_EVENT_LIMIT + 5, $this->event());

        $document = SeriesSchema::document($this->series(), $events);

        $this->assertCount(SeriesSchema::SUB_EVENT_LIMIT, $document['subEvent']);
    }

    public function test_it_encodes_to_valid_json(): void
    {
        $schema = SeriesSchema::forSeries($this->series(['short' => "line one\nline two \\ \"quoted\""]));
        $json = json_encode($schema);

        $this->assertIsString($json);
        $this->assertSame($schema, json_decode($json, true));
    }
}
