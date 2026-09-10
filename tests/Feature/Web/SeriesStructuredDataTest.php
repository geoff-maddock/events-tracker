<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\Event;
use App\Models\OccurrenceType;
use App\Models\Series;
use App\Models\User;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The series page and listing emit EventSeries JSON-LD with the full set of
 * recommended Event properties, and upcoming instances inherit the series
 * venue's address.
 */
class SeriesStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * @return array<int, array<string, mixed>> every ld+json block on the page, decoded
     */
    private function structuredData(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $this->assertNotEmpty($matches[1], 'the page emitted no JSON-LD block');

        return array_map(function (string $json) {
            $decoded = json_decode($json, true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD did not parse: '.json_last_error_msg());

            return $decoded;
        }, $matches[1]);
    }

    private function venueWithAddress(): Entity
    {
        $venue = Entity::factory()->venue()->create(['name' => 'Squirrel Hill Sports Bar']);
        $venue->locations()->create([
            'name' => 'Main',
            'slug' => 'main-'.$venue->id,
            'address_one' => '4305 Murray Ave',
            'city' => 'Pittsburgh',
            'state' => 'PA',
            'postcode' => '15217',
            'location_type_id' => 1,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => User::factory()->create()->id,
        ]);

        return $venue;
    }

    private function series(array $overrides = []): Series
    {
        return Series::factory()->create(array_merge([
            'name' => 'Gloom Nights',
            'slug' => 'gloom-nights',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'occurrence_type_id' => OccurrenceType::where('name', 'Weekly')->value('id'),
            'venue_id' => $this->venueWithAddress()->id,
            'ticket_link' => 'https://tickets.example/gloom',
            'door_price' => '10.00',
        ], $overrides));
    }

    public function test_the_series_page_emits_a_complete_event_series_node(): void
    {
        $series = $this->series();
        Event::factory()->create([
            'series_id' => $series->id,
            'venue_id' => null,
            'name' => 'Gloom Nights: Next',
            'start_at' => Carbon::now()->addDays(3)->setTime(21, 0),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => User::factory()->create()->id,
        ]);

        [$node] = $this->structuredData($this->get('/series/'.$series->slug)->assertOk()->getContent());

        $this->assertSame('EventSeries', $node['@type']);
        foreach (['startDate', 'endDate', 'eventStatus', 'eventAttendanceMode', 'image', 'description', 'location', 'offers', 'performer', 'organizer'] as $field) {
            $this->assertArrayHasKey($field, $node, "missing $field");
        }

        $this->assertSame('https://tickets.example/gloom', $node['offers']['url']);
        $this->assertSame('10.00', $node['offers']['price']);
        $this->assertNotEmpty($node['organizer']['url']);
        $this->assertSame('4305 Murray Ave', $node['location']['address']['streetAddress']);

        // The venueless instance inherits the series venue, address included.
        $this->assertSame('Gloom Nights: Next', $node['subEvent'][0]['name']);
        $this->assertSame('Squirrel Hill Sports Bar', $node['subEvent'][0]['location']['name']);
        $this->assertSame('4305 Murray Ave', $node['subEvent'][0]['location']['address']['streetAddress']);
        $this->assertSame($node['subEvent'][0]['startDate'], $node['startDate']);
    }

    public function test_the_series_listing_items_carry_the_same_properties(): void
    {
        $this->series();

        $blocks = $this->structuredData($this->get('/series')->assertOk()->getContent());
        $list = collect($blocks)->firstWhere('@type', 'ItemList');
        $item = collect($list['itemListElement'])->pluck('item')->firstWhere('name', 'Gloom Nights');

        $this->assertNotNull($item, 'the series was not listed');
        foreach (['startDate', 'endDate', 'eventStatus', 'offers', 'performer', 'organizer', 'location'] as $field) {
            $this->assertArrayHasKey($field, $item, "missing $field");
        }
    }
}
