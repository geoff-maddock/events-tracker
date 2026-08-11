<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\Event;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The event detail page's JSON-LD, which had no coverage at all.
 *
 * It used to be assembled by interpolating values into a JSON string literal.
 * Blade's {{ }} runs e(), which escapes quotes but leaves newlines and
 * backslashes intact — and an unescaped control character inside a JSON string
 * is invalid per RFC 8259, so any event with a multi-line description shipped
 * markup no parser would accept. The assertions here are deliberately about
 * the document parsing, not about how it is built.
 */
class EventShowStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * Pull the first application/ld+json block out of a rendered page.
     */
    private function structuredData(string $html): array
    {
        $this->assertMatchesRegularExpression(
            '#<script type="application/ld\+json">#',
            $html,
            'the page emitted no JSON-LD block'
        );

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $decoded = json_decode($matches[1], true);

        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'JSON-LD did not parse: '.json_last_error_msg()."\n".$matches[1]
        );

        return $decoded;
    }

    private function event(array $overrides = []): Event
    {
        return Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => User::factory()->create()->id,
        ], $overrides));
    }

    public function test_the_event_page_emits_parseable_json_ld(): void
    {
        $event = $this->event(['name' => 'Camp Gloom']);

        $data = $this->structuredData($this->get('/events/'.$event->slug)->assertOk()->getContent());

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('Event', $data['@type']);
        $this->assertSame('Camp Gloom', $data['name']);
    }

    public function test_json_ld_survives_a_description_that_would_break_string_interpolation(): void
    {
        // The exact regression: a newline, a backslash and a double quote.
        // Against the previous template this assertion fails.
        $event = $this->event([
            'name' => 'Sound\\Text "Experiments"',
            'short' => '',
            'description' => "First line\nSecond line with a \\ backslash and a \"quoted\" phrase.",
        ]);

        $data = $this->structuredData($this->get('/events/'.$event->slug)->assertOk()->getContent());

        $this->assertSame('Sound\\Text "Experiments"', $data['name']);
        $this->assertStringContainsString('backslash', $data['description']);
        $this->assertStringNotContainsString("\n", $data['description']);
    }

    public function test_the_page_carries_the_fields_search_console_asks_for(): void
    {
        $event = $this->event();

        $data = $this->structuredData($this->get('/events/'.$event->slug)->assertOk()->getContent());

        foreach (['startDate', 'endDate', 'eventStatus', 'location', 'offers', 'performer', 'organizer'] as $field) {
            $this->assertArrayHasKey($field, $data, "missing {$field}");
        }
    }

    public function test_start_date_carries_a_daylight_saving_aware_offset(): void
    {
        $event = $this->event(['start_at' => '2026-08-01 21:00:00']);

        $data = $this->structuredData($this->get('/events/'.$event->slug)->assertOk()->getContent());

        $this->assertSame('2026-08-01T21:00:00-04:00', $data['startDate']);
    }

    public function test_a_guarded_venue_address_is_not_published(): void
    {
        $venue = Entity::factory()->venue()->create();
        $venue->locations()->create([
            'name' => 'Secret Spot',
            'slug' => 'secret-spot',
            'address_one' => '123 Undisclosed Way',
            'city' => 'Pittsburgh',
            'state' => 'PA',
            'postcode' => '15201',
            'location_type_id' => 1,
            'visibility_id' => Visibility::VISIBILITY_GUARDED,
            'created_by' => User::factory()->create()->id,
        ]);

        $event = $this->event(['venue_id' => $venue->id]);

        $response = $this->get('/events/'.$event->slug)->assertOk();
        $data = $this->structuredData($response->getContent());

        $this->assertSame($venue->name, $data['location']['name']);
        $this->assertArrayNotHasKey('address', $data['location']);
        $response->assertDontSee('123 Undisclosed Way', false);
    }
}
