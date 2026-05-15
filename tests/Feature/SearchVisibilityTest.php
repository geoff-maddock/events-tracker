<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for the visibility-leak bug fixed in PR #1886.
 *
 * Background: PagesController::search() previously chained orWhere() clauses
 * for tag/series/venue/promoter/related-entity matches, then appended
 * ->where(visible). Due to Eloquent boolean precedence, the visibility
 * scope only bound to the last orWhere, so private/proposal events could
 * leak into search results via any of the relationship matches. The fix
 * wraps the OR group in a closure so visible() ANDs against the whole group.
 *
 * Each test creates a private (or proposal) event owned by user A that
 * matches the keyword via one of the OR branches, then searches as a
 * different user (and as a guest) to assert the event never appears in
 * the visible result set.
 *
 * Note: the primary "text on the event's own name/short/description" OR
 * branch uses MySQL FULLTEXT, which under InnoDB doesn't reflect uncommitted
 * rows inside a transaction. Since RefreshDatabase wraps each test in a
 * rolled-back transaction, exercising the FT branch here would always
 * return zero rows (test would pass for the wrong reason). The relationship
 * OR branches below use EXISTS subqueries, which DO see uncommitted data,
 * and they exercise the exact same OR-group structure that the bug was in.
 */
class SearchVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private SearchService $service;
    private User $owner;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SearchService();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    public function test_private_event_does_not_leak_via_tag_match(): void
    {
        $tag = Tag::factory()->create(['name' => 'Zorblaxcore']);
        $event = $this->makePrivateEvent();
        $event->tags()->attach($tag);

        $this->assertNotVisibleTo($this->other, 'Zorblaxcore', $event);
        $this->assertNotVisibleTo(null, 'Zorblaxcore', $event);
    }

    public function test_private_event_does_not_leak_via_venue_match(): void
    {
        $venue = Entity::factory()->venue()->create(['name' => 'Zorblax Hall']);
        $event = $this->makePrivateEvent(['venue_id' => $venue->id]);

        $this->assertNotVisibleTo($this->other, 'Zorblax Hall', $event);
        $this->assertNotVisibleTo(null, 'Zorblax Hall', $event);
    }

    public function test_private_event_does_not_leak_via_promoter_match(): void
    {
        $promoter = Entity::factory()->promoter()->create(['name' => 'Zorblax Promotions']);
        $event = $this->makePrivateEvent(['promoter_id' => $promoter->id]);

        $this->assertNotVisibleTo($this->other, 'Zorblax Promotions', $event);
        $this->assertNotVisibleTo(null, 'Zorblax Promotions', $event);
    }

    public function test_private_event_does_not_leak_via_related_entity_slug(): void
    {
        $entity = Entity::factory()->create(['name' => 'Zorblax Band', 'slug' => 'zorblax-band']);
        $event = $this->makePrivateEvent();
        $event->entities()->attach($entity);

        $this->assertNotVisibleTo($this->other, 'zorblax-band', $event);
        $this->assertNotVisibleTo(null, 'zorblax-band', $event);
    }

    public function test_proposal_event_does_not_leak_via_tag_match(): void
    {
        $tag = Tag::factory()->create(['name' => 'Zorblaxprop']);
        $event = $this->makePrivateEvent(['visibility_id' => Visibility::VISIBILITY_PROPOSAL]);
        $event->tags()->attach($tag);

        $this->assertNotVisibleTo($this->other, 'Zorblaxprop', $event);
        $this->assertNotVisibleTo(null, 'Zorblaxprop', $event);
    }

    public function test_owner_can_find_their_own_private_event_via_tag(): void
    {
        // The flip side: visibility filtering should ALLOW the owner to find
        // their own private events. This catches an over-correction where
        // the AND-wrap accidentally hides everyone's private content.
        $tag = Tag::factory()->create(['name' => 'Zorblaxowned']);
        $event = $this->makePrivateEvent();
        $event->tags()->attach($tag);

        $this->assertVisibleTo($this->owner, 'Zorblaxowned', $event);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePrivateEvent(array $overrides = []): Event
    {
        return Event::factory()->create(array_merge([
            'name'          => 'Zorblax Secret Show',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'created_by'    => $this->owner->id,
        ], $overrides));
    }

    private function assertNotVisibleTo(?User $user, string $keyword, Event $event): void
    {
        $ids = $this->idsFor($keyword, $user);
        $this->assertNotContains(
            $event->id,
            $ids,
            sprintf(
                'Expected event %d to be HIDDEN from %s searching "%s", but it appeared. Returned ids: [%s]',
                $event->id,
                $user ? "user {$user->id}" : 'guest',
                $keyword,
                implode(',', $ids)
            )
        );
    }

    private function assertVisibleTo(?User $user, string $keyword, Event $event): void
    {
        $ids = $this->idsFor($keyword, $user);
        $this->assertContains(
            $event->id,
            $ids,
            sprintf(
                'Expected event %d to be VISIBLE to %s searching "%s", but it was missing. Returned ids: [%s]',
                $event->id,
                $user ? "user {$user->id}" : 'guest',
                $keyword,
                implode(',', $ids)
            )
        );
    }

    /**
     * @return array<int, int>
     */
    private function idsFor(string $keyword, ?User $user): array
    {
        $results = $this->service->all($keyword, $user, 50);
        return collect($results['events']->items())->pluck('id')->all();
    }
}
