<?php

namespace Tests\Unit\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Link;
use App\Models\Location;
use App\Models\Role;
use App\Models\Series;
use App\Models\Visibility;
use App\Services\SeriesSchema;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * The schema.org EventSeries mapping.
 *
 * Built from unsaved models with their relations set by hand, the way
 * EventSchemaTest is — SeriesSchema reads relations through helpers that
 * short-circuit on relationLoaded(), so pre-setting them keeps these off the
 * database and proves the eager-loaded path is the one running.
 */
class SeriesSchemaTest extends TestCase
{
    /**
     * A series with every relation SeriesSchema touches already loaded and
     * empty, so nothing here can silently fall through to a query.
     */
    private function series(array $attributes = []): Series
    {
        $series = new Series(array_merge([
            'name' => 'Lazercrunk',
            'slug' => 'lazercrunk',
        ], $attributes));

        $series->setRelation('photos', new Collection());
        $series->setRelation('entities', new Collection());
        $series->setRelation('visibility', null);
        $series->setRelation('venue', null);
        $series->setRelation('promoter', null);
        $series->setRelation('upcomingEvent', null);
        $series->setRelation('latestEvent', null);

        return $series;
    }

    private function event(array $attributes = []): Event
    {
        $event = new Event(array_merge([
            'name' => 'Lazercrunk: Camp Gloom',
            'slug' => 'lazercrunk-camp-gloom',
            'start_at' => '2099-08-01 22:00:00',
        ], $attributes));

        $event->setRelation('photos', new Collection());
        $event->setRelation('entities', new Collection());
        $event->setRelation('visibility', null);
        $event->setRelation('venue', null);
        $event->setRelation('promoter', null);
        $event->setRelation('series', null);

        return $event;
    }

    private function venue(string $name = 'Brillobox'): Entity
    {
        $venue = new Entity(['name' => $name, 'slug' => 'brillobox']);
        $venue->setRelation('links', new Collection());
        $venue->setRelation('photos', new Collection());
        $venue->setRelation('locations', new Collection([
            (function () {
                $location = new Location([
                    'address_one' => '4104 Penn Ave',
                    'city' => 'Pittsburgh',
                    'state' => 'PA',
                    'postcode' => '15224',
                    'country' => 'USA',
                ]);
                $location->setRelation('visibility', new Visibility(['name' => 'Public']));

                return $location;
            })(),
        ]));

        return $venue;
    }

    private function performer(string $name, ?string $url = null): Entity
    {
        $entity = new Entity(['name' => $name, 'slug' => str($name)->slug()->value()]);
        $entity->setRelation('roles', new Collection([new Role(['slug' => 'dj', 'name' => 'DJ'])]));
        $entity->setRelation('links', new Collection($url ? [new Link(['url' => $url, 'is_primary' => 1])] : []));

        return $entity;
    }

    // -- the recommended-field floor --------------------------------------
    //
    // EventSeries is an Event subtype, so Search Console holds it to the same
    // list. The September 2026 export counted 52 series pages each missing
    // endDate, offers, eventStatus and performer; none of those may go absent
    // again, whatever the series has on file.

    public function test_a_series_with_nothing_on_file_still_emits_every_recommended_field(): void
    {
        $node = SeriesSchema::forSeries($this->series(['founded_at' => '2008-06-06 22:00:00']));

        foreach (['startDate', 'endDate', 'eventAttendanceMode', 'eventStatus', 'image', 'description', 'location', 'offers', 'performer'] as $field) {
            $this->assertArrayHasKey($field, $node, "missing: $field");
        }
    }

    public function test_the_document_carries_the_context_and_the_series_type(): void
    {
        $node = SeriesSchema::document($this->series());

        $this->assertSame('https://schema.org', $node['@context']);
        $this->assertSame('EventSeries', $node['@type']);
        $this->assertSame(route('series.show', 'lazercrunk'), $node['url']);
    }

    // -- dates ------------------------------------------------------------

    public function test_the_start_date_is_the_next_instance(): void
    {
        // A series has no single date; the date a searcher would act on is
        // the next instantiated event's.
        $series = $this->series(['founded_at' => '2008-06-06 22:00:00']);
        $series->setRelation('upcomingEvent', $this->event(['start_at' => '2099-08-01 22:00:00']));

        $node = SeriesSchema::forSeries($series);

        $this->assertSame('2099-08-01T22:00:00-04:00', $node['startDate']);
    }

    public function test_a_dormant_series_dates_from_its_last_instance(): void
    {
        // Every instance is in the past, so nextEvent() is null. The series
        // still ran, and the date it last ran is a true one.
        $series = $this->series(['founded_at' => '2008-06-06 22:00:00']);
        $series->setRelation('latestEvent', $this->event(['start_at' => '2019-03-15 22:00:00']));

        $this->assertSame('2019-03-15T22:00:00-04:00', SeriesSchema::forSeries($series)['startDate']);
    }

    public function test_a_series_with_no_instances_falls_back_to_when_it_was_founded(): void
    {
        $node = SeriesSchema::forSeries($this->series(['founded_at' => '2008-06-06 22:00:00']));

        $this->assertSame('2008-06-06T22:00:00-04:00', $node['startDate']);
    }

    public function test_an_end_date_is_derived_when_none_is_on_file(): void
    {
        // Event::DEFAULT_LENGTH hours after the start, the same stand-in
        // EventTime::endsAt() makes for an event.
        $node = SeriesSchema::forSeries($this->series(['founded_at' => '2008-06-06 22:00:00']));

        $this->assertSame('2008-06-07T02:00:00-04:00', $node['endDate']);
    }

    public function test_a_series_with_no_date_at_all_omits_the_date_keys(): void
    {
        // A null date reads as a malformed one rather than an absent one.
        $node = SeriesSchema::forSeries($this->series());

        $this->assertArrayNotHasKey('startDate', $node);
        $this->assertArrayNotHasKey('endDate', $node);
    }

    // -- status, offers, organizer ----------------------------------------

    public function test_a_cancelled_series_says_so(): void
    {
        $node = SeriesSchema::forSeries($this->series(['cancelled_at' => '2026-01-01 00:00:00']));

        $this->assertSame('https://schema.org/EventCancelled', $node['eventStatus']);
    }

    public function test_the_offer_states_the_currency_even_with_no_price(): void
    {
        $offer = SeriesSchema::forSeries($this->series())['offers'];

        $this->assertSame('USD', $offer['priceCurrency']);
        $this->assertArrayNotHasKey('price', $offer);
        $this->assertSame(route('series.show', 'lazercrunk'), $offer['url']);
    }

    public function test_the_offer_uses_the_door_price_and_the_ticket_link(): void
    {
        $offer = SeriesSchema::forSeries($this->series([
            'door_price' => '5.00',
            'ticket_link' => 'https://example.test/tickets',
        ]))['offers'];

        $this->assertSame('5.00', $offer['price']);
        $this->assertSame('https://example.test/tickets', $offer['url']);
    }

    public function test_a_ticket_link_that_is_not_a_url_is_not_used(): void
    {
        $offer = SeriesSchema::forSeries($this->series(['ticket_link' => 'Admission: Free']))['offers'];

        $this->assertSame(route('series.show', 'lazercrunk'), $offer['url']);
    }

    public function test_the_venue_stands_in_as_organizer_when_there_is_no_promoter(): void
    {
        $series = $this->series();
        $series->setRelation('venue', $this->venue());

        $node = SeriesSchema::forSeries($series);

        $this->assertSame('Organization', $node['organizer']['@type']);
        $this->assertSame('Brillobox', $node['organizer']['name']);
        $this->assertSame(route('entities.show', 'brillobox'), $node['organizer']['url']);
    }

    // -- location ---------------------------------------------------------

    public function test_the_location_carries_a_normalised_address(): void
    {
        $series = $this->series();
        $series->setRelation('venue', $this->venue());

        $address = SeriesSchema::forSeries($series)['location']['address'];

        $this->assertSame('4104 Penn Ave', $address['streetAddress']);
        $this->assertSame('US', $address['addressCountry']);
    }

    public function test_a_venueless_series_still_locates_to_the_city(): void
    {
        $node = SeriesSchema::forSeries($this->series());

        $this->assertSame('TBA', $node['location']['name']);
        $this->assertSame('Pittsburgh', $node['location']['address']['addressLocality']);
    }

    // -- performers, description, image -----------------------------------

    public function test_performers_come_from_the_related_dj_entities(): void
    {
        $series = $this->series();
        $series->setRelation('entities', new Collection([
            $this->performer('Keebs'),
            $this->performer('Cutups', 'https://example.test/cutups'),
        ]));

        $performers = SeriesSchema::forSeries($series)['performer'];

        // Sorted by name, and an entity with no external link gets its page here.
        $this->assertSame('Cutups', $performers[0]['name']);
        $this->assertSame('https://example.test/cutups', $performers[0]['url']);
        $this->assertSame('Keebs', $performers[1]['name']);
        $this->assertSame(route('entities.show', 'keebs'), $performers[1]['url']);
    }

    public function test_a_series_with_no_performers_stands_in_for_itself(): void
    {
        $performers = SeriesSchema::forSeries($this->series())['performer'];

        $this->assertSame('PerformingGroup', $performers[0]['@type']);
        $this->assertSame('Lazercrunk', $performers[0]['name']);
    }

    public function test_the_description_strips_markup(): void
    {
        $node = SeriesSchema::forSeries($this->series(['short' => "<p>anything goes\nbass music</p>"]));

        $this->assertSame('anything goes bass music', $node['description']);
    }

    public function test_a_series_with_no_copy_gets_a_built_description(): void
    {
        $series = $this->series();
        $series->setRelation('venue', $this->venue());

        $this->assertStringContainsString('Lazercrunk at Brillobox', SeriesSchema::forSeries($series)['description']);
    }

    public function test_the_image_falls_back_to_the_site_promo(): void
    {
        $node = SeriesSchema::forSeries($this->series());

        $this->assertSame([url('/images/arcane-city-promo.jpg')], $node['image']);
    }

    // -- subEvents --------------------------------------------------------

    public function test_upcoming_instances_are_emitted_as_sub_events(): void
    {
        $node = SeriesSchema::forSeries($this->series(), [$this->event()]);

        $this->assertCount(1, $node['subEvent']);
        $this->assertSame('Event', $node['subEvent'][0]['@type']);
        $this->assertSame('Lazercrunk: Camp Gloom', $node['subEvent'][0]['name']);
    }

    public function test_past_instances_are_left_out(): void
    {
        $node = SeriesSchema::forSeries($this->series(), [$this->event(['start_at' => '2008-06-06 22:00:00'])]);

        $this->assertArrayNotHasKey('subEvent', $node);
    }

    public function test_an_instance_with_no_venue_inherits_the_series_venue(): void
    {
        $series = $this->series();
        $series->setRelation('venue', $this->venue());

        $node = SeriesSchema::forSeries($series, [$this->event()]);

        $this->assertSame('Brillobox', $node['subEvent'][0]['location']['name']);
    }
}
