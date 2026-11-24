<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * An event or entity is found by its own text or through something it is
 * linked to. The search collects the ids from each of those routes first
 * (#2393); every route still finds its row, and a row found by several is
 * listed once.
 *
 * InnoDB FULLTEXT doesn't see rows created inside the test transaction (see
 * SearchVisibilityTest), so the own-text route is covered by the two-letter
 * keyword, which searches the name with LIKE.
 */
class SearchMatchRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function event(array $attributes = []): Event
    {
        return Event::factory()->create($attributes + [
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'series_id' => null,
            'venue_id' => null,
            'promoter_id' => null,
        ]);
    }

    private function entity(array $attributes = []): Entity
    {
        return Entity::factory()->create($attributes + ['entity_status_id' => EntityStatus::ACTIVE]);
    }

    /**
     * @return list<int>
     */
    private function found(string $section, string $keyword): array
    {
        $results = app(SearchService::class)->all($keyword, null);

        $ids = collect($results[$section]->items())->pluck('id')->all();
        $this->assertSame(count($ids), $results[$section.'Count'], 'The count matches the rows.');

        return $ids;
    }

    #[Test]
    public function an_event_is_found_through_each_thing_it_is_linked_to(): void
    {
        $byTag = $this->event();
        $byTag->tags()->attach(Tag::factory()->create(['name' => 'Zzqtag Sound']));

        $byEntity = $this->event();
        $byEntity->entities()->attach($this->entity(['name' => 'Other Name', 'slug' => 'zzq-related-act']));

        $bySeries = $this->event(['series_id' => Series::factory()->create(['name' => 'Zzqseries Night'])->id]);
        $byVenue = $this->event(['venue_id' => $this->entity(['name' => 'Zzqvenue Hall'])->id]);
        $byPromoter = $this->event(['promoter_id' => $this->entity(['name' => 'Zzqpromoter Crew'])->id]);
        $this->event();

        $this->assertSame([$byTag->id], $this->found('events', 'zzqtag sound'));
        // a related entity matches by slug, so by the keyword as a slug
        $this->assertSame([$byEntity->id], $this->found('events', 'Zzq Related Act'));
        $this->assertSame([$bySeries->id], $this->found('events', 'ZZQSERIES NIGHT'));
        $this->assertSame([$byVenue->id], $this->found('events', 'zzqvenue hall'));
        $this->assertSame([$byPromoter->id], $this->found('events', 'zzqpromoter crew'));
        $this->assertSame([], $this->found('events', 'zzqnothing here'));
    }

    #[Test]
    public function an_event_found_by_several_routes_is_listed_once(): void
    {
        $venue = $this->entity(['name' => 'Zzqboth', 'slug' => 'zzqboth']);
        $event = $this->event(['venue_id' => $venue->id, 'promoter_id' => $venue->id]);
        $event->entities()->attach($venue);
        $event->tags()->attach(Tag::factory()->create(['name' => 'zzqboth']));

        $this->assertSame([$event->id], $this->found('events', 'zzqboth'));
    }

    #[Test]
    public function a_short_keyword_searches_the_event_name(): void
    {
        $event = $this->event(['name' => 'Night of Qz']);

        $this->assertContains($event->id, $this->found('events', 'qz'));
    }

    #[Test]
    public function a_private_event_is_not_found_through_its_links(): void
    {
        $event = $this->event(['visibility_id' => Visibility::VISIBILITY_PRIVATE]);
        $event->tags()->attach(Tag::factory()->create(['name' => 'zzqsecret']));

        $this->assertSame([], $this->found('events', 'zzqsecret'));
    }

    #[Test]
    public function an_entity_is_found_by_tag_and_by_alias_and_listed_once(): void
    {
        $byTag = $this->entity();
        $byTag->tags()->attach(Tag::factory()->create(['name' => 'Zzqgenre']));

        $byAlias = $this->entity();
        $byAlias->aliases()->attach(Alias::create(['name' => 'Zzqalias']));

        $both = $this->entity();
        $both->tags()->attach(Tag::factory()->create(['name' => 'zzqsame']));
        $both->aliases()->attach(Alias::create(['name' => 'ZZQSAME']));

        $this->assertSame([$byTag->id], $this->found('entities', 'zzqgenre'));
        $this->assertSame([$byAlias->id], $this->found('entities', 'zzqalias'));
        $this->assertSame([$both->id], $this->found('entities', 'zzqsame'));
    }

    #[Test]
    public function an_unlisted_related_entity_leads_only_an_admin_to_its_events(): void
    {
        $event = $this->event();
        $event->entities()->attach($this->entity(['slug' => 'zzq-hidden-act', 'entity_status_id' => EntityStatus::UNLISTED]));

        $this->assertSame([], $this->found('events', 'zzq hidden act'));

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');
        $this->actingAs($admin);

        $results = app(SearchService::class)->all('zzq hidden act', $admin);
        $this->assertSame([$event->id], collect($results['events']->items())->pluck('id')->all());
    }

    #[Test]
    public function the_api_search_reads_a_token(): void
    {
        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $private = $this->event(['name' => 'Qz private night', 'visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $owner->id]);
        $token = $owner->createToken('search')->plainTextToken;

        $ids = fn ($response) => collect($response->json('results.events'))->pluck('id')->all();

        $this->assertNotContains($private->id, $ids($this->getJson('/api/search?q=qz&types=events&limit=20')));

        $withToken = $this->getJson('/api/search?q=qz&types=events&limit=20', ['Authorization' => 'Bearer '.$token]);
        $withToken->assertOk();
        $this->assertContains($private->id, $ids($withToken));

        // a token that is not one is a guest, not an error
        $this->app['auth']->forgetGuards();
        $this->assertNotContains($private->id, $ids($this->getJson('/api/search?q=qz&types=events&limit=20', ['Authorization' => 'Bearer nope'])->assertOk()));
    }

    #[Test]
    public function the_api_search_still_reads_the_sites_own_session(): void
    {
        // the typeahead on the site's pages calls it with the session, not a token
        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $private = $this->event(['name' => 'Qz private night', 'visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $owner->id]);

        $response = $this->actingAs($owner)->getJson('/api/search?q=qz&types=events&limit=20')->assertOk();

        $this->assertContains($private->id, collect($response->json('results.events'))->pluck('id')->all());
    }

    #[Test]
    public function an_unlisted_entity_is_not_found_by_its_alias(): void
    {
        $entity = $this->entity(['entity_status_id' => EntityStatus::UNLISTED]);
        $entity->aliases()->attach(Alias::create(['name' => 'Zzqhidden']));

        $this->assertSame([], $this->found('entities', 'zzqhidden'));
    }
}
