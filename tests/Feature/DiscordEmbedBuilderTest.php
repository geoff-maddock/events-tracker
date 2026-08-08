<?php

namespace Tests\Feature;

use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\Tag;
use App\Services\Integrations\Discord\DiscordEmbedBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Event → Discord embed rendering (issue #2058).
 *
 * The highest-value assertions here are the negative ones. Discord answers an
 * empty or over-long field with an opaque 400 that names no field, so an event
 * missing a venue or a ticket link must omit those keys entirely rather than
 * send a blank string. A bug of that shape is invisible until it reaches
 * production and then impossible to diagnose from the response.
 */
class DiscordEmbedBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function builder(): DiscordEmbedBuilder
    {
        return new DiscordEmbedBuilder;
    }

    /**
     * @return array<string, mixed>
     */
    private function embedFor(Event $event, ?DiscordTarget $target = null): array
    {
        $payload = $this->builder()->forEvent($event, $target ?? DiscordTarget::factory()->create());

        return $payload['embeds'][0];
    }

    /**
     * @param  array<string, mixed>  $embed
     * @return array<int, string>
     */
    private function fieldNames(array $embed): array
    {
        return array_column($embed['fields'] ?? [], 'name');
    }

    public function test_it_links_to_the_slug_url_not_the_id(): void
    {
        $event = Event::factory()->create(['name' => 'Warehouse Night']);

        $embed = $this->embedFor($event);

        $this->assertSame('Warehouse Night', $embed['title']);
        $this->assertStringContainsString('/events/'.$event->slug, $embed['url']);
        $this->assertStringNotContainsString('/events/'.$event->id, $embed['url']);
    }

    public function test_an_event_without_a_venue_omits_the_field_entirely(): void
    {
        $event = Event::factory()->create(['venue_id' => null]);

        $embed = $this->embedFor($event);

        // Not "present but empty" — Discord 400s on an empty field value.
        $this->assertNotContains('Where', $this->fieldNames($embed));

        foreach ($embed['fields'] as $field) {
            $this->assertNotSame('', trim($field['value']));
        }
    }

    public function test_an_event_without_a_ticket_link_omits_the_field(): void
    {
        $withLink = Event::factory()->create(['ticket_link' => 'https://tickets.example.com/abc']);
        $without = Event::factory()->create(['ticket_link' => null]);

        $this->assertContains('Tickets', $this->fieldNames($this->embedFor($withLink)));
        $this->assertNotContains('Tickets', $this->fieldNames($this->embedFor($without)));
    }

    public function test_an_unpriced_event_omits_the_price_field(): void
    {
        $unknown = Event::factory()->create(['presale_price' => null, 'door_price' => null]);
        $this->assertNotContains('Price', $this->fieldNames($this->embedFor($unknown)));

        // Event's own mutators coerce a zero price to null, so a free show is
        // indistinguishable from an unpriced one and neither claims "Free".
        $zero = Event::factory()->create(['presale_price' => 0, 'door_price' => 0]);
        $this->assertNotContains('Price', $this->fieldNames($this->embedFor($zero)));
    }

    public function test_both_prices_render_together(): void
    {
        $event = Event::factory()->create(['presale_price' => 15, 'door_price' => 20]);

        $embed = $this->embedFor($event);
        $price = $embed['fields'][array_search('Price', $this->fieldNames($embed), true)]['value'];

        $this->assertSame('$15 presale / $20 door', $price);
    }

    public function test_the_date_uses_a_viewer_local_timestamp(): void
    {
        $event = Event::factory()->create();

        $embed = $this->embedFor($event);
        $when = $embed['fields'][array_search('When', $this->fieldNames($embed), true)]['value'];

        // <t:unix:F> renders in each viewer's own timezone rather than baking
        // one timezone's string into the message.
        $this->assertStringContainsString('<t:'.$event->start_at->timestamp.':F>', $when);
    }

    public function test_over_long_content_is_clamped_rather_than_rejected(): void
    {
        // Assigned in memory rather than saved: the DB columns are narrower
        // than Discord's limits, so a persisted row can't reach them.
        $event = Event::factory()->create();
        $event->name = Str::random(400);
        $event->short = Str::random(5000);

        $embed = $this->embedFor($event);

        $this->assertLessThanOrEqual(256, mb_strlen($embed['title']));
        $this->assertLessThanOrEqual(4096, mb_strlen($embed['description']));
        $this->assertLessThanOrEqual(25, count($embed['fields']));
    }

    public function test_tags_become_the_footer(): void
    {
        $event = Event::factory()->create();
        $event->tags()->attach(Tag::factory()->create(['name' => 'techno'])->id);

        $embed = $this->embedFor($event->fresh());

        $this->assertStringContainsString('techno', $embed['footer']['text']);
    }

    public function test_an_event_with_no_tags_omits_the_footer(): void
    {
        $event = Event::factory()->create();

        $this->assertArrayNotHasKey('footer', $this->embedFor($event));
    }

    public function test_target_presentation_overrides_are_applied(): void
    {
        $target = DiscordTarget::factory()->create([
            'mention' => '@here',
            'username' => 'Show Bot',
            'embed_color' => 0x00FF00,
        ]);

        $payload = $this->builder()->forEvent(Event::factory()->create(), $target);

        $this->assertSame('@here', $payload['content']);
        $this->assertSame('Show Bot', $payload['username']);
        $this->assertSame(0x00FF00, $payload['embeds'][0]['color']);
    }

    public function test_the_digest_lists_events_within_the_description_limit(): void
    {
        $events = Event::factory()->count(3)->create();
        $target = DiscordTarget::factory()->create();

        $payload = $this->builder()->forDigest(
            $target,
            $events,
            now(),
            now()->addWeek(),
        );

        $description = $payload['embeds'][0]['description'];

        foreach ($events as $event) {
            $this->assertStringContainsString('/events/'.$event->slug, $description);
        }

        $this->assertLessThanOrEqual(4096, mb_strlen($description));
    }

    public function test_the_digest_truncates_on_whole_lines(): void
    {
        // 200 events cannot fit in 4096 characters; the description must stop
        // between entries rather than mid-link, which would render as broken
        // markdown in the channel.
        $events = Event::factory()->count(200)->create();

        $payload = $this->builder()->forDigest(
            DiscordTarget::factory()->create(),
            $events,
            now(),
            now()->addWeek(),
        );

        $description = $payload['embeds'][0]['description'];

        $this->assertLessThanOrEqual(4096, mb_strlen($description));

        foreach (explode("\n", $description) as $line) {
            $this->assertSame(
                substr_count($line, '['),
                substr_count($line, ']('),
                'A digest line was cut mid-link: '.$line
            );
        }
    }

    public function test_a_test_message_carries_no_mention(): void
    {
        $target = DiscordTarget::factory()->create(['mention' => '@everyone']);

        $payload = $this->builder()->forTest($target);

        // Pinging a whole server to check a webhook works would be rude.
        $this->assertArrayNotHasKey('content', $payload);
        $this->assertStringContainsString($target->name, $payload['embeds'][0]['description']);
    }
}
