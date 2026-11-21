<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Link;
use App\Models\Series;
use App\Models\User;
use App\Models\Visibility;
use App\Services\Embeds\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The four embed loader routes that events, entities and series each have
 * (#2406): an HTML fragment by id for the page's own ajax call, JSON by slug,
 * each in a full and a minimal (first embed only, small player) form.
 */
class EmbedLoaderRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->app->instance(Provider::class, new SoundcloudOnlyProvider());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function kinds(): array
    {
        return ['events' => ['events'], 'entities' => ['entities'], 'series' => ['series']];
    }

    /**
     * A public record of the kind with two playable links.
     */
    private function subject(string $kind, int $visibility = Visibility::VISIBILITY_PUBLIC): Event|Entity|Series
    {
        $links = 'https://soundcloud.com/example/track-one and https://soundcloud.com/example/track-two';

        if ($kind === 'entities') {
            $entity = Entity::factory()->create();
            foreach (['one', 'two'] as $track) {
                $entity->links()->attach(Link::factory()->create(['url' => 'https://soundcloud.com/example/track-'.$track])->id);
            }

            return $entity;
        }

        $owner = User::factory()->create();

        return $kind === 'events'
            ? Event::factory()->create(['description' => $links, 'visibility_id' => $visibility, 'created_by' => $owner->id])
            : Series::factory()->create(['description' => $links, 'visibility_id' => $visibility, 'created_by' => $owner->id]);
    }

    private static function noun(string $kind): string
    {
        return ['events' => 'event', 'entities' => 'entity', 'series' => 'series'][$kind];
    }

    #[DataProvider('kinds')]
    public function test_the_fragment_routes_answer_the_pages_ajax_call(string $kind): void
    {
        $subject = $this->subject($kind);
        $noun = self::noun($kind);

        $full = $this->get("/$kind/{$subject->id}/load-embeds", self::AJAX)->assertOk()
            ->assertJsonPath('Message', "Added embeds to $noun page.");
        $this->assertSame(2, substr_count($full->json('Success'), '<iframe'));
        $this->assertStringContainsString('height: 120px', $full->json('Success'));

        $minimal = $this->get("/$kind/{$subject->id}/load-minimal-embeds", self::AJAX)->assertOk()
            ->assertJsonPath('Message', "Added embeds to $noun page.");
        $this->assertSame(1, substr_count($minimal->json('Success'), '<iframe'));
        $this->assertStringContainsString('tracks/1&', $minimal->json('Success'));
        $this->assertStringContainsString('height: 24px', $minimal->json('Success'));
    }

    #[DataProvider('kinds')]
    public function test_the_fragment_routes_send_anything_else_back(string $kind): void
    {
        $subject = $this->subject($kind);

        foreach (['load-embeds', 'load-minimal-embeds'] as $route) {
            // not the page's ajax call
            $this->from('/somewhere')->get("/$kind/{$subject->id}/$route")->assertRedirect('/somewhere');
            // no such record
            $this->from('/somewhere')->get("/$kind/999999999/$route", self::AJAX)->assertRedirect('/somewhere');
        }
    }

    #[DataProvider('kinds')]
    public function test_the_slug_routes_return_json(string $kind): void
    {
        $subject = $this->subject($kind);

        $full = $this->getJson("/$kind/{$subject->slug}/embeds")->assertOk()
            ->assertJsonPath('message', 'Embeds loaded successfully')->assertJsonCount(2, 'data');
        $this->assertStringContainsString('height: 120px', $full->json('data.0'));

        $minimal = $this->getJson("/$kind/{$subject->slug}/minimal-embeds")->assertOk()
            ->assertJsonPath('message', 'Embeds loaded successfully')->assertJsonCount(1, 'data');
        $this->assertStringContainsString('height: 24px', $minimal->json('data.0'));
        $this->assertStringContainsString('tracks/1&', $minimal->json('data.0'));
        $this->get("/$kind/{$subject->slug}/minimal-embeds", self::AJAX)->assertOk()->assertJsonCount(1, 'data');

        // the same routes count an ajax call as wanting JSON
        $this->get("/$kind/{$subject->slug}/embeds", self::AJAX)->assertOk()->assertJsonCount(2, 'data');
    }

    #[DataProvider('kinds')]
    public function test_the_slug_routes_report_a_missing_record(string $kind): void
    {
        $subject = $this->subject($kind);

        foreach (['embeds', 'minimal-embeds'] as $route) {
            $this->getJson("/$kind/no-such-slug-zz/$route")->assertNotFound()
                ->assertExactJson(['error' => ucfirst(self::noun($kind)).' not found']);
            $this->from('/somewhere')->get("/$kind/no-such-slug-zz/$route")->assertRedirect('/somewhere');
            // found, but asked for as a page
            $this->from('/somewhere')->get("/$kind/{$subject->slug}/$route")->assertRedirect('/somewhere');
        }
    }

    public function test_a_private_event_or_series_is_treated_as_missing(): void
    {
        foreach (['events', 'series'] as $kind) {
            $subject = $this->subject($kind, Visibility::VISIBILITY_PRIVATE);

            $this->getJson("/$kind/{$subject->slug}/embeds")->assertNotFound();
            $this->getJson("/$kind/{$subject->slug}/minimal-embeds")->assertNotFound();
            $this->from('/somewhere')->get("/$kind/{$subject->id}/load-embeds", self::AJAX)->assertRedirect('/somewhere');
            $this->from('/somewhere')->get("/$kind/{$subject->id}/load-minimal-embeds", self::AJAX)->assertRedirect('/somewhere');

            // signed in as someone else it is still missing
            $this->actingAs(User::factory()->create());
            $this->getJson("/$kind/{$subject->slug}/embeds")->assertNotFound();
            $this->from('/somewhere')->get("/$kind/{$subject->id}/load-embeds", self::AJAX)->assertRedirect('/somewhere');

            // its owner gets all four
            $this->actingAs(User::findOrFail($subject->created_by));
            $this->getJson("/$kind/{$subject->slug}/embeds")->assertOk()->assertJsonCount(2, 'data');
            $this->getJson("/$kind/{$subject->slug}/minimal-embeds")->assertOk()->assertJsonCount(1, 'data');
            $this->get("/$kind/{$subject->id}/load-embeds", self::AJAX)->assertOk()->assertJsonStructure(['Success']);
            $this->get("/$kind/{$subject->id}/load-minimal-embeds", self::AJAX)->assertOk()->assertJsonStructure(['Success']);
            $this->app['auth']->forgetGuards();
        }
    }
}

/**
 * Answers SoundCloud oembed lookups with a canned player and everything else
 * with nothing, without touching the network.
 */
class SoundcloudOnlyProvider extends Provider
{
    public function request(string $url): void
    {
        // a different track number for each link, so a test can tell which embed it was given
        $track = str_contains($url, 'track-two') ? 2 : 1;
        $this->setResponse(str_contains($url, 'soundcloud.com/oembed')
            ? (string) json_encode(['html' => '<iframe src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/'.$track.'&visual=true"></iframe>'])
            : '');
    }

    public function requestWithoutRedirects(string $url): ?string
    {
        $this->setResponse('');

        return null;
    }
}
